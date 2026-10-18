<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now(config('reservation.timezone'))->startOfDay();

        $collectedTotal = (float) Payment::query()->collected()->sum('amount');

        return view('admin.dashboard', [
            'courts' => Court::query()->orderBy('name')->get(),
            'totalCourts' => Court::count(),
            'activeCourts' => Court::query()->active()->count(),

            'pendingCount' => Reservation::query()
                ->where('status', ReservationStatus::Pending->value)->count(),
            'confirmedCount' => Reservation::query()
                ->where('status', ReservationStatus::Confirmed->value)->count(),
            'todayCount' => Reservation::query()
                ->whereDate('starts_at', $today->toDateString())
                ->whereIn('status', [
                    ReservationStatus::Pending->value,
                    ReservationStatus::Confirmed->value,
                ])->count(),

            // On-site payments are the administrator's main manual task.
            'onSitePending' => Payment::query()
                ->where('method', PaymentMethod::OnSite->value)
                ->where('status', PaymentStatus::Pending->value)
                ->whereHas('reservation', fn ($q) => $q
                    ->where('status', ReservationStatus::Pending->value)
                    ->whereNotNull('payment_deadline')
                    ->where('payment_deadline', '>', now()))
                ->with(['reservation.court', 'reservation.user'])
                ->orderBy('created_at')
                ->limit(5)
                ->get(),

            'todaysReservations' => Reservation::query()
                ->with(['court', 'user'])
                ->whereDate('starts_at', $today->toDateString())
                ->whereIn('status', [
                    ReservationStatus::Pending->value,
                    ReservationStatus::Confirmed->value,
                ])
                ->orderBy('starts_at')
                ->limit(6)
                ->get(),

            // Grouped by action and actor so one bulk job does not fill the feed.
            'recentActivity' => AuditLog::query()
                ->with('user')
                ->latest('created_at')
                ->limit(25)
                ->get()
                ->unique(fn ($log) => $log->action.'|'.$log->user_id)
                ->take(6)
                ->values(),

            'collectedTotal' => $collectedTotal,
            'revenueThisMonth' => (float) Payment::query()
                ->collected()
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'customerCount' => User::query()->where('role', Role::Customer->value)->count(),
        ]);
    }
}
