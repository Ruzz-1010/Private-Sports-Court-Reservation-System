<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendConfirmationEmail;
use App\Models\AuditLog;
use App\Models\Court;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $statuses = array_map(fn (ReservationStatus $status) => $status->value, ReservationStatus::cases());
        $filters = $request->validate([
            'status' => ['nullable', Rule::in($statuses)],
            'court' => ['nullable', 'integer', Rule::exists('courts', 'id')],
            'date' => ['nullable', 'date'],
        ]);

        $reservations = Reservation::query()
            ->with(['court:id,name', 'user:id,name,email', 'settledPayment:id,reservation_id,status,amount'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['court'] ?? null, fn ($query, $court) => $query->where('court_id', $court))
            ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('starts_at', $date))
            ->orderByDesc('starts_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reservations.index', [
            'reservations' => $reservations,
            'courts' => Court::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => $statuses,
            'filters' => $filters,
        ]);
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['court', 'user', 'payments.verifier', 'receipt']);

        return view('admin.reservations.show', compact('reservation'));
    }

    /**
     * Send the confirmation email and receipt again.
     *
     * Staff need this when a customer paid on site but never received the
     * email, for example because the queue worker was not running or the
     * stored address had a typo that was corrected afterwards.
     */
    public function resendConfirmation(Request $request, Reservation $reservation): RedirectResponse
    {
        // Only a confirmed, paid reservation has a receipt and a confirmation
        // email worth resending. A pending hold would only confuse the customer.
        if ($reservation->receipt === null) {
            return back()->withErrors([
                'email' => 'This reservation has no receipt yet, so there is nothing to resend. Verify the payment first.',
            ]);
        }

        if ($reservation->user?->email === null) {
            return back()->withErrors([
                'email' => 'This reservation has no customer email address.',
            ]);
        }

        try {
            SendConfirmationEmail::dispatch($reservation->id);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'email' => 'The confirmation email could not be queued: '.$exception->getMessage(),
            ]);
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'reservation.confirmation_email_resent',
            'entity_type' => $reservation->getMorphClass(),
            'entity_id' => $reservation->id,
            'details' => [
                'reservation' => $reservation->reference_number,
                'email' => $reservation->user->email,
            ],
        ]);

        return back()->with('status', "Confirmation email and receipt queued again for {$reservation->user->email}.");
    }
}
