<?php

namespace App\Contracts;

use App\Models\Reservation;

/**
 * Provider-independent payment operations.
 *
 * The application depends on this interface, never on a provider SDK, so the
 * payment workflow stays testable and a second provider can be added without
 * touching reservation, receipt, or reporting logic.
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
     * Ask the provider for the current state of a payment, server to server.
     *
     * This is the only trusted source of payment truth. Maya publishes no
     * per-request webhook signature for Dynamic QRPh, so a callback is never
     * trusted on its own: the webhook handler calls this method and compares
     * the real amount, currency, status, and reference against the local
     * payment attempt before anything is confirmed.
     *
     * @return array{status: string, amount: int|float|null, currency: string|null, reference: string|null}
     */
    public function fetchSession(string $sessionId): array;

    /**
     * Identifier recorded on the payment attempt.
     */
    public function name(): string;
}
