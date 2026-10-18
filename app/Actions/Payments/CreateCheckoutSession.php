<?php

namespace App\Actions\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates the provider checkout session for an online reservation.
 */
class CreateCheckoutSession
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * @throws RuntimeException when the provider is unavailable
     */
    public function handle(Reservation $reservation): Payment
    {
        $session = $this->gateway->createCheckoutSession($reservation);

        return DB::transaction(function () use ($reservation, $session): Payment {
            $payment = $reservation->payments()
                ->where('method', PaymentMethod::Online->value)
                ->where('status', PaymentStatus::Pending->value)
                ->latest('id')
                ->first();

            if ($payment === null) {
                $payment = $reservation->payments()->firstOrFail();
            }

            $payment->update([
                'provider' => $this->gateway->name(),
                'provider_checkout_session_id' => $session['id'],
                'provider_metadata' => array_merge($payment->provider_metadata ?? [], [
                    'checkout_url' => $session['url'],
                ]),
            ]);

            return $payment;
        });
    }
}
