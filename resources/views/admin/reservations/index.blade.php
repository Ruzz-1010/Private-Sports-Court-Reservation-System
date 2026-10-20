@extends('layouts.admin')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm font-semibold text-indigo-600">Booking operations</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Reservations</h1>
        <p class="mt-2 text-sm text-slate-600">Review every booking, its customer, payment state, and court schedule.</p>
    </div>
    <a href="{{ route('admin.reports.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">View reports</a>
</div>

<form method="GET" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
    <label class="text-sm font-medium text-slate-700">Status
        <select name="status" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-medium text-slate-700">Court
        <select name="court" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">All courts</option>
            @foreach ($courts as $court)<option value="{{ $court->id }}" @selected((string) ($filters['court'] ?? '') === (string) $court->id)>{{ $court->name }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-medium text-slate-700">Reservation date
        <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="mt-1.5 w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </label>
    <div class="flex items-end gap-2"><button class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Apply filters</button><a href="{{ route('admin.reservations.index') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Reset</a></div>
</form>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><p class="text-sm font-semibold text-slate-950">{{ $reservations->total() }} reservations</p><p class="text-xs text-slate-500">Newest schedule first</p></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Booking</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Schedule</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($reservations as $reservation)
                <tr class="hover:bg-slate-50/80">
                    <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $reservation->court?->name }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">{{ $reservation->reference_number }}</p></td>
                    <td class="px-5 py-4"><p class="font-medium text-slate-800">{{ $reservation->user?->name }}</p><p class="text-xs text-slate-500">{{ $reservation->user?->email }}</p></td>
                    <td class="px-5 py-4 text-slate-700"><p>{{ $reservation->starts_at->setTimezone(config('reservation.timezone'))->format('M j, Y') }}</p><p class="text-xs text-slate-500">{{ $reservation->starts_at->setTimezone(config('reservation.timezone'))->format('g:i A') }}–{{ $reservation->ends_at->setTimezone(config('reservation.timezone'))->format('g:i A') }}</p></td>
                    <td class="px-5 py-4 font-semibold text-slate-900">₱{{ number_format((float) $reservation->total_amount, 2) }}</td>
                    <td class="px-5 py-4"><x-reservation-status :status="$reservation->status->value" /></td>
                    <td class="px-5 py-4 text-right"><a href="{{ route('admin.reservations.show', $reservation) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Details</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-500">No reservations match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($reservations->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $reservations->links() }}</div>@endif
</div>
@endsection
