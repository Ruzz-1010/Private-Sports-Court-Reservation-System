<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DemoPaymentController extends Controller
{
    public function show(Reservation $reservation): View
    {
        abort_unless(config('payments.gateway') === 'demo', 404);
        $this->authorize('view', $reservation);
        $payment = $reservation->payments()->where('status', PaymentStatus::Pending->value)->latest('id')->firstOrFail();

        return view('customer.payments.demo', compact('reservation', 'payment'));
    }

    public function simulate(Reservation $reservation, ConfirmPayment $confirmPayment): RedirectResponse
    {
        abort_unless(config('payments.gateway') === 'demo', 404);
        $this->authorize('view', $reservation);
        $payment = $reservation->payments()->where('status', PaymentStatus::Pending->value)->latest('id')->firstOrFail();
        $confirmPayment->handle($payment, null, ['demo' => true, 'simulation' => 'paymongo_qrph']);

        return redirect()->route('customer.reservations.show', $reservation)->with('status', 'Demo payment verified. Your reservation is confirmed.');
    }
}
