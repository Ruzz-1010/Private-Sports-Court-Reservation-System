<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $reservations = $user->reservations()->with('court');

        $upcoming = (clone $reservations)
            ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Confirmed->value])
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')
            ->get();

        return view('customer.dashboard', [
            'user' => $user,
            'courts' => Court::query()->active()->orderBy('name')->get(),
            'nextBooking' => $upcoming->first(),
            'upcomingBookings' => $upcoming->take(3),
            'totalReservations' => $user->reservations()->count(),
            'pendingCount' => (clone $reservations)
                ->where('status', ReservationStatus::Pending->value)->count(),
            'confirmedCount' => (clone $reservations)
                ->where('status', ReservationStatus::Confirmed->value)->count(),
            'receiptCount' => $user->reservations()->has('receipt')->count(),
            'totalSpent' => Payment::query()
                ->collected()
                ->whereHas('reservation', fn ($q) => $q->where('user_id', $user->id))
                ->sum('amount'),
        ]);
    }
}
