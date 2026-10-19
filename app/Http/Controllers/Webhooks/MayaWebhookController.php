<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmationEmail;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\Payments\MayaGateway;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Confirms a Maya Dynamic QRPh payment only after a server-to-server lookup.
 * Browser redirects and unverified webhook payloads never settle a booking.
 */
class MayaWebhookController extends Controller
{
    public function __construct(
        private readonly MayaGateway $gateway,
        private readonly ConfirmPayment $confirmPayment,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $paymentId = $payload['id'] ?? null;

        if (! is_string($paymentId) || $paymentId === '') {
            $this->reject($request, 'missing_payment_id');

            return response()->json(['message' => 'Missing Maya payment ID.'], 422);
        }

        if (PaymentEvent::where('provider', 'maya')->where('provider_event_id', $paymentId)->exists()) {
            return response()->json(['message' => 'Event already processed.']);
        }

        $payment = Payment::query()
            ->where('provider', 'maya')
            ->where('provider_checkout_session_id', $paymentId)
            ->latest('id')
            ->first();

        if ($payment === null) {
            $this->storeEvent($paymentId, (string) ($payload['paymentStatus'] ?? 'unknown'), 'unmatched');

            return response()->json(['message' => 'Payment not found.']);
        }

        $verified = $this->gateway->fetchSession($paymentId);
        $mismatch = $this->mismatchReason($payment, $verified);

        if ($mismatch !== null) {
            $payment->update(['status' => PaymentStatus::RequiresReview]);
            $this->storeEvent($paymentId, (string) ($payload['paymentStatus'] ?? 'unknown'), 'requires_review', $mismatch);

            return response()->json(['message' => 'Payment requires review.']);
        }

        $result = $this->confirmPayment->handle($payment, null, [
            'maya_payment_id' => $paymentId,
            'maya_status' => $verified['status'],
        ]);

        $this->storeEvent(
            $paymentId,
            (string) ($payload['paymentStatus'] ?? 'PAYMENT_SUCCESS'),
            $result['duplicate'] ? 'duplicate' : 'processed',
        );

        if (! $result['duplicate'] && $result['receipt'] !== null) {
            SendConfirmationEmail::dispatch($result['reservation']->id);
        }

        return response()->json(['message' => 'OK']);
    }

    /** @param array{status: string, amount: int|float|null, currency: string|null, reference: string|null} $verified */
    private function mismatchReason(Payment $payment, array $verified): ?string
    {
        if (! in_array($verified['status'], ['PAYMENT_SUCCESS', 'COMPLETED'], true)) {
            return 'Maya has not confirmed this payment.';
        }

        if ($verified['amount'] === null || number_format((float) $verified['amount'], 2, '.', '') !== (string) $payment->amount) {
            return 'Maya amount does not match the reservation.';
        }

        if ($verified['currency'] !== $payment->currency) {
            return 'Maya currency does not match the reservation.';
        }

        $expectedReference = $payment->provider_metadata['maya_request_reference'] ?? null;

        if (! is_string($expectedReference) || $verified['reference'] !== $expectedReference) {
            return 'Maya reference does not match the payment attempt.';
        }

        return null;
    }

    private function storeEvent(string $id, string $type, string $status, ?string $reason = null): void
    {
        try {
            PaymentEvent::create([
                'provider' => 'maya',
                'provider_event_id' => $id,
                'event_type' => $type,
                'processing_status' => $status,
                'received_at' => now(),
                'notes' => $reason === null ? null : json_encode(['reason' => $reason]),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Concurrent webhook retries represent the same provider event.
        }
    }

    private function reject(Request $request, string $reason): void
    {
        AuditLog::create([
            'action' => 'payment.webhook_rejected',
            'entity_type' => (new PaymentEvent)->getMorphClass(),
            'details' => ['provider' => 'maya', 'reason' => $reason],
            'ip_address' => $request->ip(),
        ]);
    }
}
