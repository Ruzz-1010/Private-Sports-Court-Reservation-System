<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\ConfirmPayment;
use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmationEmail;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Reservation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives asynchronous payment provider callbacks.
 *
 * Order matters: authenticate first, then de-duplicate, then validate, and only
 * then change any state. A browser redirect is never treated as proof of
 * payment, so this endpoint is the only path that confirms an online booking.
 */
class PayMongoWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly ConfirmPayment $confirmPayment,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header(config('payments.webhook.signature_header'));

        if (! $this->gateway->verifyWebhookSignature($rawBody, $signature)) {
            $this->recordRejected($request, 'invalid_signature');

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Malformed payload.'], 422);
        }

        $eventId = $this->eventId($payload);
        $eventType = $this->eventType($payload);

        // Idempotency: a redelivered event must not repeat any work.
        if ($eventId !== null && PaymentEvent::where('provider_event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Event already processed.']);
        }

        $result = $this->process($payload, $eventId, $eventType);

        return response()->json(['message' => 'OK', 'result' => $result]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function process(array $payload, ?string $eventId, ?string $eventType): array
    {
        $attributes = $payload['data']['attributes'] ?? [];
        $session = is_array($attributes['data'] ?? null) ? $attributes['data'] : [];

        $reference = $session['attributes']['metadata']['reservation_reference']
            ?? $attributes['data']['attributes']['reference_number']
            ?? null;

        if (! is_string($reference)) {
            $this->storeEvent($eventId, $eventType, 'unmatched', ['reason' => 'no reservation reference']);

            return ['outcome' => 'unmatched'];
        }

        $reservation = Reservation::where('reference_number', $reference)->first();

        if ($reservation === null) {
            $this->storeEvent($eventId, $eventType, 'unmatched', ['reason' => 'reservation not found']);

            return ['outcome' => 'unmatched'];
        }

        $payment = $this->pendingPaymentFor($reservation, $session);

        if ($payment === null) {
            $this->storeEvent($eventId, $eventType, 'unmatched', ['reason' => 'no pending payment']);

            return ['outcome' => 'unmatched'];
        }

        $mismatch = $this->validateAmountAndStatus($payment, $session);

        if ($mismatch !== null) {
            $payment->update([
                'status' => PaymentStatus::RequiresReview,
                'provider_metadata' => $this->safeMetadata($session),
            ]);

            $this->storeEvent($eventId, $eventType, 'requires_review', ['reason' => $mismatch]);

            return ['outcome' => 'requires_review', 'reason' => $mismatch];
        }

        $payment->update([
            'provider_checkout_session_id' => $session['id'] ?? $payment->provider_checkout_session_id,
            'provider_payment_id' => $attributes['data']['id'] ?? null,
        ]);

        $result = $this->confirmPayment->handle($payment, null, [
            'event_id' => $eventId,
            'event_type' => $eventType,
        ]);

        $this->storeEvent($eventId, $eventType, $result['duplicate'] ? 'duplicate' : 'processed', [
            'reservation' => $reference,
        ]);

        if (! $result['duplicate'] && $result['receipt'] !== null) {
            SendConfirmationEmail::dispatch($result['reservation']->id);
        }

        return [
            'outcome' => $result['duplicate'] ? 'duplicate' : 'confirmed',
            'reservation' => $reference,
            'status' => $result['reservation']->status->value,
        ];
    }

    /**
     * The payment amount must match what we quoted, in the same currency.
     *
     * @param  array<string, mixed>  $session
     */
    private function validateAmountAndStatus(Payment $payment, array $session): ?string
    {
        $sessionAttributes = $session['attributes'] ?? [];

        $paid = $sessionAttributes['status'] ?? null;

        if ($paid !== null && $paid !== 'paid') {
            return "payment status was {$paid}";
        }

        $amount = $sessionAttributes['amount'] ?? null;

        if (is_numeric($amount)) {
            $expected = (int) round((float) $payment->amount * 100);

            if ((int) $amount !== $expected) {
                return 'amount mismatch';
            }
        }

        $currency = $sessionAttributes['currency'] ?? null;

        if (is_string($currency) && $currency !== $payment->currency) {
            return 'currency mismatch';
        }

        return null;
    }

    /**
     * Find the attempt this callback settles. Later attempts on the same
     * reservation are possible, so prefer the most recent pending one.
     *
     * @param  array<string, mixed>  $session
     */
    private function pendingPaymentFor(Reservation $reservation, array $session): ?Payment
    {
        $sessionId = $session['id'] ?? null;

        if (is_string($sessionId)) {
            $bySession = $reservation->payments()
                ->where('provider_checkout_session_id', $sessionId)
                ->latest('id')
                ->first();

            if ($bySession !== null) {
                return $bySession;
            }
        }

        return $reservation->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->latest('id')
            ->first();
    }

    /**
     * Persist only non-sensitive fields. Never store the raw payload, which can
     * contain billing details.
     *
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    private function safeMetadata(array $session): array
    {
        return array_filter([
            'checkout_session_id' => $session['id'] ?? null,
            'status' => $session['attributes']['status'] ?? null,
            'amount' => $session['attributes']['amount'] ?? null,
            'currency' => $session['attributes']['currency'] ?? null,
        ], fn ($value) => $value !== null);
    }

    private function eventId(array $payload): ?string
    {
        $id = $payload['data']['id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function eventType(array $payload): ?string
    {
        $type = $payload['data']['type'] ?? null;

        return is_string($type) && $type !== '' ? $type : null;
    }

    private function storeEvent(?string $eventId, ?string $eventType, string $status, array $notes = []): void
    {
        if ($eventId === null) {
            return;
        }

        try {
            PaymentEvent::create([
                'provider' => $this->gateway->name(),
                'provider_event_id' => $eventId,
                'event_type' => $eventType ?? 'unknown',
                'processing_status' => $status,
                'received_at' => now(),
                'notes' => json_encode($notes),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Two workers handled the same event concurrently; that is fine.
        }
    }

    private function recordRejected(Request $request, string $reason): void
    {
        Log::warning('Rejected payment webhook.', ['reason' => $reason, 'ip' => $request->ip()]);

        AuditLog::create([
            'action' => 'payment.webhook_rejected',
            'entity_type' => (new PaymentEvent)->getMorphClass(),
            'details' => ['reason' => $reason],
            'ip_address' => $request->ip(),
        ]);
    }
}
