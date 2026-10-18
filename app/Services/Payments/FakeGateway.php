<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Reservation;

/**
 * Local gateway used for development, testing, and presentations.
 *
 * It produces a real signed webhook payload and posts it to the application's
 * own webhook endpoint, so the production code path is exercised end to end:
 * signature verification, idempotency, and the confirmation transaction all
 * run exactly as they would against PayMongo. Only the network hop is removed.
 */
class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    /**
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Reservation $reservation): array
    {
        $sessionId = 'cs_'.strtolower(substr(hash('sha256', $reservation->reference_number), 0, 24));

        return [
            'id' => $sessionId,
            'url' => route('payments.simulator', ['reference' => $reservation->reference_number]),
        ];
    }

    /**
     * Signs with the same secret the webhook endpoint verifies against, so the
     * signature check is real rather than bypassed.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = config('payments.paymongo.webhook_secret') ?: 'fake-webhook-secret';

        return $signature !== null
            && hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    public function fetchSession(string $sessionId): array
    {
        return ['status' => 'unknown', 'amount' => null, 'currency' => null, 'reference' => null];
    }
}
