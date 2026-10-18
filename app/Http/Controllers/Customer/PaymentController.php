<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Payments\CreateCheckoutSession;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private readonly CreateCheckoutSession $createCheckoutSession) {}

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

        // The fake gateway returns its simulator route instead of a provider URL.
        if ($url === null) {
            return redirect()->route('customer.reservations.show', $reservation);
        }

        return redirect()->away($url);
    }
}
