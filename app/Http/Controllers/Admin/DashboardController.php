<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'courts' => Court::query()->orderBy('name')->get(),
            'totalCourts' => Court::count(),
            'pendingCount' => Reservation::query()
                ->where('status', ReservationStatus::Pending->value)
                ->count(),
            'confirmedCount' => Reservation::query()
                ->where('status', ReservationStatus::Confirmed->value)
                ->count(),
            'collectedTotal' => Payment::query()->collected()->sum('amount'),
        ]);
    }
}
