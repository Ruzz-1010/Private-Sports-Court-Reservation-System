<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $statuses = array_map(fn (ReservationStatus $status) => $status->value, ReservationStatus::cases());
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'court' => ['nullable', 'integer', Rule::exists('courts', 'id')],
            'status' => ['nullable', Rule::in($statuses)],
        ]);

        $timezone = config('reservation.timezone');
        $from = CarbonImmutable::parse($filters['from'] ?? now($timezone)->startOfMonth()->toDateString(), $timezone)->startOfDay();
        $to = CarbonImmutable::parse($filters['to'] ?? now($timezone)->endOfMonth()->toDateString(), $timezone)->endOfDay();

        $reservations = Reservation::query()
            ->with(['court:id,name', 'user:id,name'])
            ->whereBetween('starts_at', [$from, $to])
            ->when($filters['court'] ?? null, fn ($query, $court) => $query->where('court_id', $court))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('starts_at')
            ->get();

        $paidPayments = Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to])
            ->when($filters['court'] ?? null, fn ($query, $court) => $query->whereHas('reservation', fn ($reservation) => $reservation->where('court_id', $court)))
            ->get(['id', 'reservation_id', 'amount', 'paid_at']);

        $utilization = $this->utilization($reservations, $from, $to);

        return view('admin.reports.index', [
            'courts' => Court::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => $statuses,
            'filters' => array_merge($filters, ['from' => $from->toDateString(), 'to' => $to->toDateString()]),
            'reservations' => $reservations,
            'reservationCount' => $reservations->count(),
            'confirmedCount' => $reservations->whereIn('status', [ReservationStatus::Confirmed->value, ReservationStatus::Completed->value])->count(),
            'revenue' => (float) $paidPayments->sum('amount'),
            'utilization' => $utilization,
        ]);
    }

    /** @param Collection<int, Reservation> $reservations */
    private function utilization(Collection $reservations, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $availableMinutesPerDay = ((int) config('reservation.operating_hours.end') - (int) config('reservation.operating_hours.start')) * 60;
        $closedDays = config('reservation.closed_days');
        $openDays = 0;

        for ($date = $from->startOfDay(); $date->lte($to); $date = $date->addDay()) {
            if (! in_array($date->dayOfWeek, $closedDays, true)) {
                $openDays++;
            }
        }

        return $reservations
            ->filter(fn (Reservation $reservation) => in_array($reservation->status, [ReservationStatus::Confirmed, ReservationStatus::Completed], true))
            ->groupBy('court_id')
            ->map(function (Collection $courtReservations) use ($availableMinutesPerDay, $openDays): array {
                $bookedMinutes = $courtReservations->sum(fn (Reservation $reservation) => $reservation->starts_at->diffInMinutes($reservation->ends_at));
                $availableMinutes = $availableMinutesPerDay * $openDays;

                return [
                    'court' => $courtReservations->first()->court,
                    'booked_minutes' => $bookedMinutes,
                    'rate' => $availableMinutes > 0 ? min(100, ($bookedMinutes / $availableMinutes) * 100) : 0,
                ];
            })
            ->sortByDesc('rate')
            ->values();
    }
}
