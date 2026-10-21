@extends('layouts.admin')

@section('content')
<x-page-heading eyebrow="Booking operations" title="Reservations" description="Every booking, customer, and court schedule in one place."><a href="{{ route('admin.reports.index') }}" class="button">View reports <span aria-hidden="true">↗</span></a></x-page-heading>

<form method="GET" class="mb-6 grid gap-3 border border-slate-700 surface p-4 shadow-sm md:grid-cols-4">
    <label class="text-sm font-medium text-slate-300">Status
        <select name="status" class="mt-1.5 w-full rounded-lg border-slate-700 text-sm focus:border-lime-300 focus:ring-lime-300">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-medium text-slate-300">Court
        <select name="court" class="mt-1.5 w-full rounded-lg border-slate-700 text-sm focus:border-lime-300 focus:ring-lime-300">
            <option value="">All courts</option>
            @foreach ($courts as $court)<option value="{{ $court->id }}" @selected((string) ($filters['court'] ?? '') === (string) $court->id)>{{ $court->name }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-medium text-slate-300">Reservation date
        <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-1.5 w-full rounded-lg border-slate-700 text-sm focus:border-lime-300 focus:ring-lime-300">
    </label>
    <div class="flex items-end gap-2"><button class="button button-primary">Apply filters</button><a href="{{ route('admin.reservations.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 hover:bg-slate-800">Reset</a></div>
</form>

<div class="overflow-hidden border border-slate-700 surface shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-700/60 px-5 py-4"><p class="text-sm font-semibold text-slate-100">{{ $reservations->total() }} reservations</p><p class="text-xs text-slate-400">Newest schedule first</p></div>
    <div class="table-scroll">
        <table class="min-w-full text-left text-sm">
            <thead class="surface-muted text-xs uppercase tracking-wide text-slate-400"><tr><th class="px-5 py-3">Booking</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Schedule</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-slate-700/60">
            @forelse ($reservations as $reservation)
                <tr class="hover:bg-slate-50/80">
                    <td class="px-5 py-4"><p class="font-semibold text-slate-100">{{ $reservation->court?->name }}</p><p class="mt-0.5 font-mono text-xs text-slate-400">{{ $reservation->reference_number }}</p></td>
                    <td class="px-5 py-4"><p class="font-medium text-slate-200">{{ $reservation->user?->name }}</p><p class="text-xs text-slate-400">{{ $reservation->user?->email }}</p></td>
                    <td class="px-5 py-4 text-slate-300"><p>{{ $reservation->starts_at->setTimezone(config('reservation.timezone'))->format('M j, Y') }}</p><p class="text-xs text-slate-400">{{ $reservation->starts_at->setTimezone(config('reservation.timezone'))->format('g:i A') }}–{{ $reservation->ends_at->setTimezone(config('reservation.timezone'))->format('g:i A') }}</p></td>
                    <td class="px-5 py-4 font-semibold text-slate-100">₱{{ number_format((float) $reservation->total_amount, 2) }}</td>
                    <td class="px-5 py-4"><x-reservation-status :status="$reservation->status->value" /></td>
                    <td class="px-5 py-4 text-right"><a href="{{ route('admin.reservations.show', $reservation) }}" class="text-sm font-semibold text-lime-300 hover:text-indigo-800">Details</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400">No reservations match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($reservations->hasPages())<div class="border-t border-slate-700/60 px-5 py-4">{{ $reservations->links() }}</div>@endif
</div>
@endsection
