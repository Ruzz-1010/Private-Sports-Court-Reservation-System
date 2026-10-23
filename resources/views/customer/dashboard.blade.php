@extends('layouts.app')

@section('content')
<div class="customer-dashboard">
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Welcome and primary action --}}
        <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#111827] shadow-2xl shadow-black/20">
            <img src="{{ asset('images/courts/featured-pickleball.png') }}" alt="" class="absolute inset-y-0 right-0 hidden h-full w-[48%] object-cover opacity-30 saturate-50 lg:block">
            <div class="absolute inset-0 bg-[linear-gradient(90deg,#111827_0%,#111827_57%,rgba(17,24,39,.62)_100%)]"></div>
            <div class="absolute -left-16 -top-20 h-52 w-52 rounded-full bg-lime-300/10 blur-3xl"></div>

            <div class="relative flex min-h-64 flex-col justify-between gap-8 p-6 sm:p-8 lg:flex-row lg:items-end lg:p-10">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-lime-300/20 bg-lime-300/10 px-3 py-1 text-xs font-bold uppercase tracking-[.16em] text-lime-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-lime-300"></span>
                        Player dashboard
                    </div>
                    <h1 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                        Welcome back, {{ explode(' ', $user->name)[0] }}.
                    </h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-400 sm:text-base">
                        Plan your next game, track every reservation, and keep your receipts in one place.
                    </p>
                    <p class="mt-5 flex items-center gap-2 text-sm text-slate-400">
                        <svg class="h-4 w-4 text-lime-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z"/></svg>
                        {{ now(config('reservation.timezone'))->format('l, F j, Y') }}
                    </p>
                </div>

                @if($courts->isNotEmpty())
                    <a href="{{ route('customer.availability.index', ['court' => $courts->first()]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-lime-300 px-5 py-3 text-sm font-bold text-[#15200c] shadow-lg shadow-lime-300/10 transition hover:-translate-y-0.5 hover:bg-lime-200 focus:outline-none focus:ring-2 focus:ring-lime-300 focus:ring-offset-2 focus:ring-offset-slate-900">
                        Book a court
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                    </a>
                @endif
            </div>
        </section>

        {{-- Key metrics --}}
        @php
            $metrics = [
                ['label' => 'Confirmed', 'value' => $confirmedCount, 'note' => 'Active reservations', 'tone' => 'lime', 'icon' => 'M5 13l4 4L19 7'],
                ['label' => 'Awaiting payment', 'value' => $pendingCount, 'note' => 'Slots currently on hold', 'tone' => 'amber', 'icon' => 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0Z'],
                ['label' => 'Receipts', 'value' => $receiptCount, 'note' => 'Verified transactions', 'tone' => 'sky', 'icon' => 'M6 2h9l3 3v17l-3-2-3 2-3-2-3 2V2Zm3 6h6m-6 4h6'],
                ['label' => 'Total paid', 'value' => '₱'.number_format((float) $totalSpent, 2), 'note' => $totalReservations.' total reservations', 'tone' => 'violet', 'icon' => 'M4 7h16v10H4V7Zm0 4h16m-5 3h2'],
            ];
        @endphp

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Reservation summary">
            @foreach($metrics as $metric)
                @php
                    $tone = match($metric['tone']) {
                        'amber' => 'bg-amber-400/10 text-amber-300 ring-amber-400/20',
                        'sky' => 'bg-sky-400/10 text-sky-300 ring-sky-400/20',
                        'violet' => 'bg-violet-400/10 text-violet-300 ring-violet-400/20',
                        default => 'bg-lime-300/10 text-lime-300 ring-lime-300/20',
                    };
                @endphp
                <article class="group rounded-2xl border border-white/10 bg-[#111827] p-5 shadow-xl shadow-black/10 transition hover:-translate-y-0.5 hover:border-white/20">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-400">{{ $metric['label'] }}</p>
                            <p class="mt-3 text-3xl font-bold tracking-tight text-white">{{ $metric['value'] }}</p>
                        </div>
                        <span class="grid h-10 w-10 place-items-center rounded-xl ring-1 {{ $tone }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $metric['icon'] }}"/></svg>
                        </span>
                    </div>
                    <p class="mt-4 text-xs text-slate-400">{{ $metric['note'] }}</p>
                </article>
            @endforeach
        </section>

        {{-- Next session --}}
        @if($nextBooking)
            <section class="overflow-hidden rounded-2xl border border-lime-300/20 bg-[linear-gradient(135deg,rgba(190,242,100,.12),rgba(17,24,39,.96)_45%)] shadow-xl shadow-black/10">
                <div class="flex flex-col gap-6 p-5 sm:p-6 lg:flex-row lg:items-center">
                    <div class="flex min-w-0 flex-1 items-center gap-4">
                        <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-lime-300 text-[#15200c] shadow-lg shadow-lime-300/10">
                            <span class="text-2xl font-bold leading-none">{{ $nextBooking->starts_at->format('j') }}</span>
                            <span class="-mt-2 text-[10px] font-bold uppercase tracking-widest">{{ $nextBooking->starts_at->format('M') }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[.16em] text-lime-300">Your next session</p>
                            <h2 class="mt-1 truncate text-xl font-bold text-white">{{ $nextBooking->court?->name ?? 'Court unavailable' }}</h2>
                            <p class="mt-1 text-sm text-slate-400">
                                {{ $nextBooking->starts_at->format('D, M j, Y') }} ·
                                {{ $nextBooking->starts_at->format('g:i A') }}–{{ $nextBooking->ends_at->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-reservation-status :status="$nextBooking->status->value"/>
                        <a href="{{ route('customer.reservations.show', $nextBooking) }}" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                            View details
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
                @if($nextBooking->status->value === 'pending')
                    <div class="border-t border-amber-300/15 bg-amber-300/5 px-5 py-3 text-sm text-amber-200 sm:px-6">
                        Payment is pending. This slot is held until {{ $nextBooking->payment_deadline->format('g:i A') }}.
                    </div>
                @endif
            </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1.45fr_.75fr]">
            {{-- Upcoming reservations --}}
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#111827] shadow-xl shadow-black/10">
                <header class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-5 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-lime-300">Schedule</p>
                        <h2 class="mt-1 text-lg font-bold text-white">Upcoming reservations</h2>
                    </div>
                    <a href="{{ route('customer.reservations.index') }}" class="text-sm font-semibold text-slate-300 transition hover:text-lime-300">View all →</a>
                </header>

                @if($upcomingBookings->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-white/5 text-slate-400 ring-1 ring-white/10">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z"/></svg>
                        </span>
                        <h3 class="mt-4 font-semibold text-white">Your schedule is clear</h3>
                        <p class="mt-1 text-sm text-slate-400">Book a court and your next session will appear here.</p>
                        @if($courts->isNotEmpty())
                            <a href="{{ route('customer.availability.index', ['court' => $courts->first()]) }}" class="mt-5 inline-flex rounded-xl bg-lime-300 px-4 py-2.5 text-sm font-bold text-[#15200c] hover:bg-lime-200">Find a time</a>
                        @endif
                    </div>
                @else
                    <ul class="divide-y divide-white/10">
                        @foreach($upcomingBookings as $booking)
                            <li>
                                <a href="{{ route('customer.reservations.show', $booking) }}" class="group flex items-center gap-4 px-5 py-4 transition hover:bg-white/[.035] sm:px-6">
                                    <div class="w-12 shrink-0 text-center">
                                        <p class="text-2xl font-bold leading-none text-white">{{ $booking->starts_at->format('j') }}</p>
                                        <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $booking->starts_at->format('M') }}</p>
                                    </div>
                                    <div class="h-10 w-px bg-white/10"></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-white group-hover:text-lime-300">{{ $booking->court?->name ?? 'Court unavailable' }}</p>
                                        <p class="mt-1 text-xs text-slate-400">{{ $booking->starts_at->format('D, g:i A') }} – {{ $booking->ends_at->format('g:i A') }}</p>
                                    </div>
                                    <x-reservation-status :status="$booking->status->value"/>
                                    <svg class="hidden h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-lime-300 sm:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Court quick picker --}}
            <aside class="overflow-hidden rounded-2xl border border-white/10 bg-[#111827] shadow-xl shadow-black/10">
                <header class="border-b border-white/10 px-5 py-5">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-lime-300">Quick booking</p>
                    <h2 class="mt-1 text-lg font-bold text-white">Choose a court</h2>
                    <p class="mt-1 text-xs text-slate-400">Check open times and current rates.</p>
                </header>
                <div class="space-y-2 p-3">
                    @forelse($courts as $court)
                        <a href="{{ route('customer.availability.index', ['court' => $court]) }}" class="group flex items-center gap-3 rounded-xl border border-transparent p-3 transition hover:border-white/10 hover:bg-white/[.04]">
                            <x-court-photo :court="$court" class="h-12 w-14 shrink-0 rounded-lg"/>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-white group-hover:text-lime-300">{{ $court->name }}</span>
                                <span class="mt-1 block text-xs text-slate-400">₱{{ number_format((float) $court->hourly_rate, 2) }} / hour</span>
                            </span>
                            <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/5 text-slate-400 transition group-hover:bg-lime-300 group-hover:text-[#15200c]">→</span>
                        </a>
                    @empty
                        <p class="px-3 py-10 text-center text-sm text-slate-400">No active courts are available.</p>
                    @endforelse
                </div>
            </aside>
        </div>

        {{-- Policies and rules --}}
        @php
            $adminFee = (float) config('reservation.refund.admin_fee_percent');
            $policies = [
                [
                    'title' => 'Refund of an online payment',
                    'icon' => 'M16 15v-1a4 4 0 00-4-4H8m0 0 3-3m-3 3 3 3M3 12a9 9 0 1018 0 9 9 0 00-18 0Z',
                    'tone' => 'sky',
                    'points' => [
                        'Ask for a refund at least '.config('reservation.refund.request_deadline_hours').' hours before your slot.',
                        'A '.rtrim(rtrim(number_format($adminFee, 2), '0'), '.').'% administration fee is deducted from what you paid.',
                        'Example: paid ₱1,000.00, then ₱'.number_format(1000 * $adminFee / 100, 2).' is kept and ₱'.number_format(1000 - (1000 * $adminFee / 100), 2).' comes back to you.',
                        'No refund once your slot has started, or if you did not show up.',
                        'A court closed by management is fully refunded, with no fee.',
                    ],
                ],
                [
                    'title' => 'No-show and account suspension',
                    'icon' => 'M12 9v4m0 4h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
                    'tone' => 'amber',
                    'points' => [
                        'A no-show means you booked and did not arrive without cancelling.',
                        'Staff record no-shows at the court management desk.',
                        'Each no-show counts for '.config('reservation.no_show.counting_window_days').' days.',
                        'After '.config('reservation.no_show.limit_before_suspension').' no-shows, management may suspend your account.',
                        'Cancel early if you cannot come — a cancelled booking is not a no-show.',
                    ],
                ],
                [
                    'title' => 'Payment and arrival',
                    'icon' => 'M4 7h16v10H4V7Zm0 4h16',
                    'tone' => 'lime',
                    'points' => [
                        'Online payment holds the slot for '.config('reservation.hold_minutes.online').' minutes.',
                        'On-site payment holds it until the day of play, but never past the start time.',
                        'A booking is confirmed only after the payment is verified.',
                        'Present your reference number or email receipt at court management.',
                    ],
                ],
            ];
        @endphp

        <section aria-label="Policies and rules" class="overflow-hidden rounded-2xl border border-white/10 bg-[#0d1319] shadow-xl shadow-black/20">
            <header class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 bg-gradient-to-r from-lime-300/[.07] to-transparent px-5 py-4 sm:px-6">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.22em] text-lime-300">Policies and rules</p>
                    <h2 class="mt-1 text-base font-bold text-white">Know before you book</h2>
                </div>
                <a href="{{ route('terms') }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[.1em] text-slate-300 transition hover:border-lime-300/40 hover:text-lime-300">Read full terms &rarr;</a>
            </header>
            <div class="grid gap-3 p-4 sm:p-5 lg:grid-cols-3">
                @foreach($policies as $policy)
                    @php
                        $tone = match($policy['tone']) {
                            'amber' => 'bg-amber-400/10 text-amber-300 ring-amber-400/25',
                            'sky' => 'bg-sky-400/10 text-sky-300 ring-sky-400/25',
                            default => 'bg-lime-300/10 text-lime-300 ring-lime-300/25',
                        };
                    @endphp
                    <article class="flex flex-col rounded-xl border border-white/[.07] bg-white/[.015] p-4 transition hover:border-white/15 hover:bg-white/[.03]">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg ring-1 {{ $tone }}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $policy['icon'] }}"/></svg>
                            </span>
                            <h3 class="text-[13px] font-bold leading-snug text-white">{{ $policy['title'] }}</h3>
                        </div>
                        <ul class="mt-3 space-y-1.5 border-t border-white/[.06] pt-3 text-[11px] leading-5 text-slate-400">
                            @foreach($policy['points'] as $point)
                                <li class="flex gap-2"><span class="mt-[6px] h-1 w-1 shrink-0 rounded-full bg-slate-600"></span><span>{{ $point }}</span></li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
