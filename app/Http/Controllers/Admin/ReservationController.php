<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
}
