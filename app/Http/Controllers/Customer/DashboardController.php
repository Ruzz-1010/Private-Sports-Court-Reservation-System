<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('customer.dashboard', [
            'courts' => Court::query()->active()->orderBy('name')->get(),
            'totalReservations' => $user->reservations()->count(),
            'upcomingCount' => $user->reservations()
                ->whereIn('status', ['confirmed', 'pending'])
                ->where('starts_at', '>=', now())
                ->count(),
            'receiptCount' => $user->reservations()->has('receipt')->count(),
        ]);
    }
}
