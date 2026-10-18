<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Reservations\CreateReservation;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\ScheduleConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Models\Court;
use App\Models\Reservation;
use App\Services\Availability\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly CreateReservation $createReservation,
    ) {}

    /**
     * Customer reservation history.
     */
    public function index(Request $request): View
    {
        $reservations = $request->user()
            ->reservations()
            ->with('court')
            ->orderBy('starts_at', 'desc')
            ->paginate(10);

        return view('customer.reservations.index', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Individual reservation detail, restricted to the owner.
     */
    public function show(Request $request, Reservation $reservation): View
    {
        $this->authorize('view', $reservation);

        $reservation->load(['court', 'payments']);

        return view('customer.reservations.show', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Booking form for a court and date.
     */
    public function create(Request $request, Court $court): View
    {
        $date = $this->resolveDate($request->string('date')->toString());
        $slots = $this->availability->slotsFor($court, $date);

        return view('customer.reservations.create', [
            'court' => $court,
            'date' => $date,
            'slots' => $slots,
            'availableSlots' => array_values(array_filter(
                $slots,
                fn ($slot) => $slot['status'] === AvailabilityService::AVAILABLE,
            )),
            'minDuration' => (int) config('reservation.min_duration_minutes'),
            'maxDuration' => (int) config('reservation.max_duration_minutes'),
            'incrementMinutes' => (int) config('reservation.increment_minutes'),
            'hourlyRate' => (float) $court->hourly_rate,
        ]);
    }

    /**
     * Store a reservation, creating a slot hold awaiting payment.
     */
    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $court = Court::findOrFail($request->integer('court_id'));

        $startsAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $request->string('date')->toString().' '.$request->string('time')->toString(),
            config('reservation.timezone'),
        );

        $endsAt = $startsAt->addMinutes((int) $request->integer('duration_minutes'));
        $method = PaymentMethod::from($request->string('payment_method')->toString());

        try {
            $reservation = $this->createReservation->handle(
                $request->user(),
                $court,
                $startsAt,
                $endsAt,
                $method,
            );
        } catch (ScheduleConflictException $e) {
            return back()->withInput()->withErrors(['slot' => $e->getMessage()]);
        }

        return redirect()
            ->route('customer.reservations.show', $reservation)
            ->with('status', 'Slot held. Complete payment to confirm your reservation.');
    }

    /**
     * Cancel a reservation and release the slot.
     */
    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('cancel', $reservation);

        DB::transaction(function () use ($reservation, $request): void {
            $reservation->update([
                'status' => ReservationStatus::Cancelled,
                'cancellation_reason' => $request->user()->isAdmin()
                    ? 'Cancelled by administrator.'
                    : 'Cancelled by customer.',
            ]);

            $reservation->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Cancelled->value]);
        });

        return redirect()
            ->route('customer.reservations.index')
            ->with('status', 'Reservation cancelled.');
    }

    private function resolveDate(?string $date): CarbonImmutable
    {
        $parsed = is_string($date) && $date !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $date, config('reservation.timezone'))
            : false;

        return $parsed === false
            ? CarbonImmutable::now(config('reservation.timezone'))->startOfDay()
            : $parsed->startOfDay();
    }
}
