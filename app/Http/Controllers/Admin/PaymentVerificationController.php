<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmationEmail;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentVerificationController extends Controller
{
    public function __construct(private readonly ConfirmPayment $confirmPayment) {}

    /**
     * On-site payments awaiting an administrator decision.
     */
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->where('method', PaymentMethod::OnSite->value)
            ->where('status', PaymentStatus::Pending->value)
            ->whereHas('reservation', fn ($query) => $query
                ->where('status', ReservationStatus::Pending->value))
            ->with(['reservation.court', 'reservation.user'])
            ->orderBy('created_at')
            ->paginate(15);

        return view('admin.payments.index', [
            'payments' => $payments,
        ]);
    }

    /**
     * Record cash received at the facility and confirm the reservation.
     */
    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $payment->loadMissing('reservation');

        if ($payment->method !== PaymentMethod::OnSite) {
            return back()->withErrors(['payment' => 'Only on-site payments can be verified here.']);
        }

        if ($payment->status !== PaymentStatus::Pending) {
            return back()->withErrors(['payment' => 'That payment has already been settled.']);
        }

        if (! $payment->reservation?->status->acceptsPayment()) {
            return back()->withErrors(['payment' => 'That reservation is no longer awaiting payment.']);
        }

        // The amount must match what was quoted when the slot was held.
        if ((float) $payment->amount !== (float) $payment->reservation->total_amount) {
            $payment->update(['status' => PaymentStatus::RequiresReview]);

            return back()->withErrors([
                'payment' => 'Amount does not match the reservation total. Marked for manual review.',
            ]);
        }

        $result = $this->confirmPayment->handle($payment, $request->user());

        if ($result['receipt'] !== null) {
            SendConfirmationEmail::dispatch($result['reservation']->id);
        }

        return back()->with('status', $result['duplicate']
            ? 'That payment was already verified.'
            : "Payment verified. Receipt {$result['receipt']?->receipt_number} issued.");
    }

    /**
     * Reject cash verification, keeping the reservation pending.
     */
    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $payment->loadMissing('reservation');

        if ($payment->method !== PaymentMethod::OnSite) {
            return back()->withErrors(['payment' => 'Only on-site payments can be rejected here.']);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Please give a reason so the customer can be informed.',
        ]);

        $payment->update([
            'status' => PaymentStatus::Failed,
            'provider_metadata' => array_merge($payment->provider_metadata ?? [], [
                'rejected_by' => $request->user()?->id,
                'rejection_reason' => $validated['reason'],
            ]),
        ]);

        return back()->with('status', 'Payment rejected. The reservation stays pending until its deadline.');
    }
}
