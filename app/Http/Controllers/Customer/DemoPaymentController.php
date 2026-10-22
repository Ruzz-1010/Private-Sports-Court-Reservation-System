<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmationEmail;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class DemoPaymentController extends Controller
{
    public function show(Reservation $reservation): View|RedirectResponse
    {
        abort_unless(config('payments.simulation_enabled') === true, 404);
        $this->authorize('view', $reservation);
        abort_unless($reservation->payment_method === PaymentMethod::Online, 404);

        if (! $reservation->holdIsActive()) {
            return redirect()->route('customer.reservations.show', $reservation)
                ->withErrors(['payment' => 'This reservation is no longer awaiting payment or its hold has expired.']);
        }

        $payment = $reservation->payments()
            ->where('method', PaymentMethod::Online->value)
            ->whereIn('provider', ['paymongo', 'paymongo_demo'])
            ->where('status', PaymentStatus::Pending->value)
            ->latest('id')
            ->firstOrFail();

        return view('customer.payments.demo', compact('reservation', 'payment'));
    }

    public function simulate(Reservation $reservation, ConfirmPayment $confirmPayment): RedirectResponse
    {
        abort_unless(config('payments.simulation_enabled') === true, 404);
        $this->authorize('view', $reservation);
        abort_unless($reservation->payment_method === PaymentMethod::Online, 404);

        // Serialize repeated clicks for this reservation. Recheck the hold under
        // the lock so an expired or already confirmed booking cannot be paid.
        $result = DB::transaction(function () use ($reservation, $confirmPayment): ?array {
            $locked = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if (! $locked->holdIsActive()) {
                return null;
            }

            $payment = $locked->payments()
                ->where('method', PaymentMethod::Online->value)
                ->whereIn('provider', ['paymongo', 'paymongo_demo'])
                ->where('status', PaymentStatus::Pending->value)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($payment === null || $payment->currency !== 'PHP'
                || $payment->amount !== $locked->total_amount) {
                return null;
            }

            return $confirmPayment->handle($payment, null, [
                'demo' => true,
                'simulation' => 'paymongo_qrph',
            ]);
        }, 3);

        $response = redirect()->route('customer.reservations.show', $reservation);

        if ($result === null) {
            return $response->withErrors([
                'payment' => 'Payment was already processed, the hold expired, or the checkout is invalid. Please check your reservation.',
            ]);
        }

        // The confirmation is committed before email dispatch. A synchronous
        // SMTP/API failure must never turn a successful payment into a 500.
        if (! $result['duplicate'] && $result['receipt'] !== null) {
            try {
                SendConfirmationEmail::dispatch($result['reservation']->id);
            } catch (Throwable $exception) {
                report($exception);

                return $response->with('status', 'Demo payment confirmed. Your receipt is available to download, but the email could not be sent. Please contact the facility.');
            }
        }

        return $response->with('status', 'Demo payment verified. Your reservation is confirmed and your receipt is available.');
    }
}
