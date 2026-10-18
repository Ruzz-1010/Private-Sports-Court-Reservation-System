<?php

namespace App\Contracts;

use App\Models\Reservation;

/**
 * Provider-independent payment operations.
 *
 * The application depends on this interface, never on a provider SDK, so the
 * gateway can be swapped for a simulator without touching business logic.
 */
interface PaymentGateway
{
    /**
     * Create a hosted checkout session for a reservation.
     *
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(Reservation $reservation): array;

    /**
     * Verify that a webhook really came from the provider.
     *
     * Receives the raw request body because any re-encoding changes the bytes
     * and breaks the HMAC comparison.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool;

    /**
     * Ask the provider for the current state of a checkout session.
     *
     * Used to reconcile a callback that never arrived.
     *
     * @return array{status: string, amount: int|null, currency: string|null, reference: string|null}
     */
    public function fetchSession(string $sessionId): array;

    /**
     * Identifier recorded on the payment attempt.
     */
    public function name(): string;
}
