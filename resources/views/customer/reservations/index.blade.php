@extends('layouts.app')

@section('content')
<x-page-heading eyebrow="Your activity" title="My reservations" description="Upcoming games, past bookings, payment status, and receipts."><a href="{{ route('customer.dashboard') }}" class="button">Explore courts <span aria-hidden="true">↗</span></a></x-page-heading>

@if ($reservations->isEmpty())
    <div class="empty-state">
        <p class="text-sm font-medium text-slate-100">You have no reservations yet.</p>
        <p class="mt-1 text-sm text-slate-400">Your next game starts with a court and a time.</p>
        <a href="{{ route('customer.dashboard') }}" class="button button-primary">Explore courts →</a>
    </div>
@else
    <div class="table-scroll border border-slate-700 surface" tabindex="0" role="region" aria-label="Reservation history">
        <table class="w-full min-w-[44rem] divide-y divide-slate-700/60">
            <caption class="sr-only">Your reservations, most recent first</caption>
            <thead class="surface-muted">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">Reference</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">Court</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">Schedule</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-400">Amount</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">Status</th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">Receipt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/60">
                @foreach ($reservations as $reservation)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('customer.reservations.show', $reservation) }}"
                               class="font-medium text-slate-100 underline">{{ $reservation->reference_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-300">{{ $reservation->court->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-300">
                            {{ $reservation->starts_at->format('M j, Y') }}<br>
                            <span class="text-xs text-slate-400">
                                {{ $reservation->starts_at->format('g:i A') }} &ndash; {{ $reservation->ends_at->format('g:i A') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-100">₱{{ number_format((float) $reservation->total_amount, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            <x-reservation-status :status="$reservation->status->value" />
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($reservation->receipt)
                                <a href="{{ route('customer.receipts.show', $reservation->receipt) }}"
                                   class="inline-flex items-center gap-1 text-sm font-medium text-slate-300 underline hover:text-white">
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
