<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Reservation;

/** Local presentation gateway. It never contacts PayMongo or moves money. */
class DemoGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paymongo_demo';
    }

    public function createCheckoutSession(Reservation $reservation): array
    {
        $id = 'demo_'.strtolower(substr(hash('sha256', $reservation->reference_number), 0, 24));

        return [
            'id' => $id,
            'url' => route('customer.payments.demo', ['reservation' => $reservation]),
            'reference' => $reservation->reference_number,
        ];
    }

    public function fetchSession(string $sessionId): array
    {
        return ['status' => 'pending', 'amount' => null, 'currency' => null, 'reference' => null, 'payment_id' => null];
    }
}
