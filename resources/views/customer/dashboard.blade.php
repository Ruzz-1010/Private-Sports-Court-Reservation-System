@extends('layouts.app')

@section('content')

{{-- Header --}}
<div class="relative mb-8 flex flex-wrap items-end justify-between gap-4 overflow-hidden rounded-[2rem] bg-slate-950 px-6 py-9 text-white shadow-xl shadow-slate-300 sm:px-9">
    <div class="absolute inset-0 -z-0 bg-[radial-gradient(circle_at_top_right,_rgba(20,184,166,0.32),transparent_28%),radial-gradient(circle_at_5%_95%,_rgba(79,70,229,0.42),transparent_34%)]"></div>
    <div class="relative z-10">
        <p class="text-sm font-semibold text-indigo-200">
            {{ now(config('reservation.timezone'))->format('l, F j, Y') }}
        </p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight text-white">
            Welcome back, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}
        </h1>
        <p class="mt-2 text-slate-300">Find your time, choose a court, and reserve in a few steps.</p>
    </div>

    @if ($courts->isNotEmpty())
        <a href="{{ route('customer.availability.index', ['court' => $courts->first()->id]) }}"
           class="relative z-10 inline-flex items-center gap-2 rounded-xl bg-teal-300 px-5 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-teal-500/20 hover:bg-teal-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Book a court
        </a>
    @endif
</div>

{{-- Next booking --}}
@if ($nextBooking)
    <div class="mb-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-900 px-6 py-3">
            <h2 class="text-sm font-semibold text-white">Your next booking</h2>
        </div>

        <div class="grid gap-6 p-6 sm:grid-cols-[auto_1fr_auto] sm:items-center">
            <div class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-lg bg-slate-100">
                <span class="text-2xl font-bold leading-none text-slate-900">{{ $nextBooking->starts_at->format('j') }}</span>
                <span class="mt-1 text-xs font-medium uppercase text-slate-500">{{ $nextBooking->starts_at->format('M') }}</span>
            </div>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $nextBooking->court->name }}</h3>
                    <x-reservation-status :status="$nextBooking->status->value" />
                </div>
                <p class="mt-1 text-slate-600">
                    {{ $nextBooking->starts_at->format('g:i A') }} &ndash; {{ $nextBooking->ends_at->format('g:i A') }}
                    <span class="text-slate-400">&middot;</span>
                    {{ $nextBooking->starts_at->diffInMinutes($nextBooking->ends_at) / 60 }} {{ Str::plural('hour', $nextBooking->starts_at->diffInMinutes($nextBooking->ends_at) / 60) }}
                </p>
                <p class="mt-1 font-mono text-xs text-slate-500">{{ $nextBooking->reference_number }}</p>
            </div>

            <a href="{{ route('customer.reservations.show', $nextBooking) }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                View details
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        @if ($nextBooking->status->value === 'pending')
            <div class="border-t border-amber-200 bg-amber-50 px-6 py-3">
                <p class="text-sm text-amber-900">
                    <span class="font-semibold">Payment pending.</span>
                    @if ($nextBooking->holdIsActive())
                        Your slot is held until
                        <span class="font-semibold">{{ $nextBooking->payment_deadline->format('g:i A') }}</span>.
                    @else
                        The hold deadline has passed and this slot may be released.
                    @endif
                </p>
            </div>
        @endif
    </div>
@endif

{{-- Stats --}}
<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                </svg>
            </span>
            <div>
                <p class="text-sm text-slate-600">Confirmed</p>
                <p class="text-2xl font-bold text-slate-900">{{ $confirmedCount }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <div>
                <p class="text-sm text-slate-600">Awaiting payment</p>
                <p class="text-2xl font-bold text-slate-900">{{ $pendingCount }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                </svg>
            </span>
            <div>
                <p class="text-sm text-slate-600">Receipts</p>
                <p class="text-2xl font-bold text-slate-900">{{ $receiptCount }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <div>
                <p class="text-sm text-slate-600">Total paid</p>
                <p class="text-2xl font-bold text-slate-900">₱{{ number_format((float) $totalSpent, 2) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">

    {{-- Upcoming list --}}
    <div class="lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">Upcoming bookings</h2>
                <a href="{{ route('customer.reservations.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">View all</a>
            </div>

            @if ($upcomingBookings->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                        </svg>
                    </span>
                    <p class="mt-3 font-medium text-slate-900">No upcoming bookings</p>
                    <p class="mt-1 text-sm text-slate-600">Your reserved courts will show up here.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($upcomingBookings as $booking)
                        <li>
                            <a href="{{ route('customer.reservations.show', $booking) }}"
                               class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50">
                                <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg bg-slate-100">
                                    <span class="text-base font-bold leading-none text-slate-900">{{ $booking->starts_at->format('j') }}</span>
                                    <span class="text-[10px] font-medium uppercase text-slate-500">{{ $booking->starts_at->format('M') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-slate-900">{{ $booking->court->name }}</p>
                                    <p class="text-sm text-slate-600">
                                        {{ $booking->starts_at->format('g:i A') }} &ndash; {{ $booking->ends_at->format('g:i A') }}
                                    </p>
                                </div>
                                <x-reservation-status :status="$booking->status->value" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Court quick links --}}
    <div>
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-900">Book by court</h2>
            </div>
            <ul class="divide-y divide-slate-200">
                @forelse ($courts as $court)
                    <li>
                        <a href="{{ route('customer.availability.index', ['court' => $court]) }}"
                           class="flex items-center gap-3 px-6 py-4 hover:bg-slate-50">
                            <x-court-photo :court="$court" class="h-11 w-14 shrink-0 rounded-md" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $court->name }}</p>
                                <p class="text-xs text-slate-600">₱{{ number_format((float) $court->hourly_rate, 2) }}/hr</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-slate-600">No courts available yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
