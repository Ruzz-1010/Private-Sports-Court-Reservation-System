<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Payments\ConfirmPayment;
use App\Actions\Payments\CreateCheckoutSession;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmationEmail;
use App\Models\Reservation;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CreateCheckoutSession $createCheckoutSession,
        private readonly PayMongoGateway $gateway,
        private readonly ConfirmPayment $confirmPayment,
    ) {}

    public function refresh(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('view', $reservation);
        $payment = $reservation->payments()->where('provider', 'paymongo')->latest('id')->first();

        if ($payment?->provider_checkout_session_id === null) {
            return back()->withErrors(['payment' => 'No PayMongo checkout was found for this reservation.']);
        }

        $verified = $this->gateway->fetchSession($payment->provider_checkout_session_id);
        if ($verified['status'] !== 'paid') {
            return back()->withErrors(['payment' => 'PayMongo has not reported this checkout as paid yet.']);
        }

        if ((int) $verified['amount'] !== (int) round(((float) $payment->amount) * 100)
            || $verified['currency'] !== $payment->currency
            || $verified['reference'] !== $reservation->reference_number
            || ! is_string($verified['payment_id'])) {
            $payment->update(['status' => PaymentStatus::RequiresReview]);

            return back()->withErrors(['payment' => 'PayMongo payment details need administrator review.']);
        }

        $result = $this->confirmPayment->handle($payment, null, [
            'paymongo_session_id' => $payment->provider_checkout_session_id,
            'paymongo_payment_id' => $verified['payment_id'],
            'confirmed_by' => 'customer_status_refresh',
        ]);
        if (! $result['duplicate'] && $result['receipt'] !== null) {
            SendConfirmationEmail::dispatch($result['reservation']->id);
        }

        return redirect()->route('customer.reservations.show', $reservation)
            ->with('status', 'Payment verified. Your reservation is now confirmed.');
    }

    /**
     * Send the customer to the provider's hosted checkout page.
     *
     * A redirect alone never confirms a reservation; the webhook does that.
     */
    public function checkout(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('view', $reservation);

        if ($reservation->payment_method !== PaymentMethod::Online) {
            return back()->withErrors(['payment' => 'This reservation is not set to online payment.']);
        }

        if (! $reservation->status->acceptsPayment()) {
            return back()->withErrors(['payment' => 'This reservation is no longer awaiting payment.']);
        }

        try {
            $payment = $this->createCheckoutSession->handle($reservation);
        } catch (RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        $url = $payment->provider_metadata['checkout_url'] ?? null;

        // PayMongo returns its hosted QR payment URL. A browser redirect alone never
        // confirms the reservation; only the verified webhook can do that.
        if ($url === null) {
            return redirect()->route('customer.reservations.show', $reservation);
        }

        return redirect()->away($url);
    }
}
