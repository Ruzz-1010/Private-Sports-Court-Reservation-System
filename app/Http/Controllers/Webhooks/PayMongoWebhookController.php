<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmationEmail;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayMongoWebhookController extends Controller
{
    public function __construct(private readonly PayMongoGateway $gateway, private readonly ConfirmPayment $confirmPayment) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->validSignature($request)) {
            $this->reject($request, 'invalid_signature');

            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        }

        $payload = $request->json()->all();
        $eventId = data_get($payload, 'data.id');
        $eventType = data_get($payload, 'data.attributes.type');
        $sessionId = data_get($payload, 'data.attributes.data.id');

        if (! is_string($eventId) || ! is_string($eventType) || ! is_string($sessionId)) {
            $this->reject($request, 'invalid_event');

            return response()->json(['message' => 'Invalid PayMongo event.'], 422);
        }

        if (PaymentEvent::where('provider', 'paymongo')->where('provider_event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Event already processed.']);
        }

        if ($eventType !== 'checkout_session.payment.paid') {
            $this->storeEvent($eventId, $eventType, 'ignored');

            return response()->json(['message' => 'Event ignored.']);
        }

        $payment = Payment::query()->where('provider', 'paymongo')->where('provider_checkout_session_id', $sessionId)->latest('id')->first();
        if ($payment === null) {
            $this->storeEvent($eventId, $eventType, 'unmatched');

            return response()->json(['message' => 'Payment not found.']);
        }

        $verified = $this->gateway->fetchSession($sessionId);
        if (($reason = $this->mismatchReason($payment, $verified)) !== null) {
            $payment->update(['status' => PaymentStatus::RequiresReview]);
            $this->storeEvent($eventId, $eventType, 'requires_review', $reason);

            return response()->json(['message' => 'Payment requires review.']);
        }

        $result = $this->confirmPayment->handle($payment, null, [
            'paymongo_session_id' => $sessionId,
            'paymongo_payment_id' => $verified['payment_id'],
        ]);
        $this->storeEvent($eventId, $eventType, $result['duplicate'] ? 'duplicate' : 'processed');
        if (! $result['duplicate'] && $result['receipt'] !== null) {
            SendConfirmationEmail::dispatch($result['reservation']->id);
        }

        return response()->json(['message' => 'OK']);
    }

    private function validSignature(Request $request): bool
    {
        $secret = config('payments.paymongo.webhook_secret');
        $header = $request->header('Paymongo-Signature');
        if (! is_string($secret) || $secret === '' || ! is_string($header)) {
            return false;
        }
        $parts = collect(explode(',', $header))->mapWithKeys(function (string $part): array {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            return is_string($key) && is_string($value) ? [$key => $value] : [];
        });
        $timestamp = $parts->get('t');
        $isTest = str_starts_with((string) config('payments.paymongo.secret_key'), 'sk_test_');
        $signature = $parts->get($isTest ? 'te' : 'li');
        if (! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($signature)) {
            return false;
        }
        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret), $signature);
    }

    /** @param array{status:string, amount:int|float|null, currency:string|null, reference:string|null, payment_id:string|null} $verified */
    private function mismatchReason(Payment $payment, array $verified): ?string
    {
        if ($verified['status'] !== 'paid') {
            return 'PayMongo has not confirmed this payment.';
        }
        if ($verified['amount'] === null || (int) $verified['amount'] !== (int) round(((float) $payment->amount) * 100)) {
            return 'PayMongo amount does not match the reservation.';
        }
        if ($verified['currency'] !== $payment->currency) {
            return 'PayMongo currency does not match the reservation.';
        }
        if ($verified['reference'] !== $payment->reservation->reference_number) {
            return 'PayMongo reference does not match the reservation.';
        }
        if (! is_string($verified['payment_id']) || $verified['payment_id'] === '') {
            return 'PayMongo did not return a payment identifier.';
        }

        return null;
    }

    private function storeEvent(string $id, string $type, string $status, ?string $reason = null): void
    {
        try {
            PaymentEvent::create(['provider' => 'paymongo', 'provider_event_id' => $id, 'event_type' => $type, 'processing_status' => $status, 'received_at' => now(), 'notes' => $reason ? json_encode(['reason' => $reason]) : null]);
        } catch (UniqueConstraintViolationException) {
        }
    }

    private function reject(Request $request, string $reason): void
    {
        AuditLog::create(['action' => 'payment.webhook_rejected', 'entity_type' => (new PaymentEvent)->getMorphClass(), 'details' => ['provider' => 'paymongo', 'reason' => $reason], 'ip_address' => $request->ip()]);
    }
}
