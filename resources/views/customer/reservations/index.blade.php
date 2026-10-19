@extends('layouts.app')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">My reservations</h1>
        <p class="mt-1 text-sm text-slate-600">Your booking history and current status.</p>
    </div>
    <a href="{{ route('customer.dashboard') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Back to dashboard
    </a>
</div>

@if ($reservations->isEmpty())
    <div class="rounded-lg border border-slate-200 bg-white p-10 text-center">
        <p class="text-sm font-medium text-slate-900">You have no reservations yet.</p>
        <p class="mt-1 text-sm text-slate-600">Check a court's availability to book your first slot.</p>
    </div>
@else
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="w-full min-w-[44rem] divide-y divide-slate-200">
            <caption class="sr-only">Your reservations, most recent first</caption>
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Reference</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Court</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Schedule</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Amount</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Receipt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach ($reservations as $reservation)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('customer.reservations.show', $reservation) }}"
                               class="font-medium text-slate-900 underline">{{ $reservation->reference_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $reservation->court->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            {{ $reservation->starts_at->format('M j, Y') }}<br>
                            <span class="text-xs text-slate-500">
                                {{ $reservation->starts_at->format('g:i A') }} &ndash; {{ $reservation->ends_at->format('g:i A') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-900">₱{{ number_format((float) $reservation->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <x-reservation-status :status="$reservation->status->value" />
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($reservation->receipt)
                                <a href="{{ route('customer.receipts.show', $reservation->receipt) }}"
                                   class="inline-flex items-center gap-1 text-sm font-medium text-slate-700 underline hover:text-slate-900">
                                    PDF
                                </a>
                            @else
                                <span class="text-sm text-slate-400">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($reservations->hasPages())
        <div class="mt-6">{{ $reservations->links() }}</div>
    @endif
@endif
@endsection
