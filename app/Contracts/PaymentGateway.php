<?php

namespace App\Contracts;

use App\Models\Reservation;

interface PaymentGateway
{
    /** @return array{id: string, url: string, reference?: string} */
    public function createCheckoutSession(Reservation $reservation): array;

    /** @return array{status: string, amount: int|float|null, currency: string|null, reference: string|null, payment_id: string|null} */
    public function fetchSession(string $sessionId): array;

    public function name(): string;
}
