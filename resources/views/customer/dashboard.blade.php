@extends('layouts.app')

@section('content')
@php
    $firstName = explode(' ', trim($user->name))[0];
    $metrics = [
        ['label' => 'Confirmed', 'value' => $confirmedCount, 'note' => 'Active reservations', 'tone' => 'lime', 'icon' => 'M5 13l4 4L19 7'],
        ['label' => 'Awaiting payment', 'value' => $pendingCount, 'note' => 'Slots currently on hold', 'tone' => 'amber', 'icon' => 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0Z'],
        ['label' => 'Receipts', 'value' => $receiptCount, 'note' => 'Verified transactions', 'tone' => 'sky', 'icon' => 'M6 2h9l3 3v17l-3-2-3 2-3-2-3 2V2Zm3 6h6m-6 4h6'],
        ['label' => 'Total paid', 'value' => '₱' . number_format((float) $totalSpent, 2), 'note' => $totalReservations . ' total reservations', 'tone' => 'violet', 'icon' => 'M4 7h16v10H4V7Zm0 4h16m-5 3h2'],
    ];
    $tones = [
        'amber' => 'bg-amber-400/10 text-amber-300 ring-amber-400/20',
        'sky' => 'bg-sky-400/10 text-sky-300 ring-sky-400/20',
        'violet' => 'bg-violet-400/10 text-violet-300 ring-violet-400/20',
        'lime' => 'bg-lime-300/10 text-lime-300 ring-lime-300/20',
    ];
@endphp

<div class="dash space-y-4 sm:space-y-5">

    {{-- Greeting bar --}}
    <section class="dash-card flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-5">
        <div class="min-w-0">
            <p class="dash-eyebrow">Player dashboard</p>
            <h1 class="mt-1 truncate text-lg font-bold tracking-tight text-white sm:text-xl">Welcome back, {{ $firstName }}.</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="dash-chip">
                <svg class="h-3.5 w-3.5 text-lime-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z"/></svg>
                {{ now(config('reservation.timezone'))->format('D, M j, Y') }}
            </span>
            @if($courts->isNotEmpty())
                <a href="{{ route('customer.availability.index', ['court' => $courts->first()]) }}" class="dash-btn-primary">
                    Book a court
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                </a>
            @endif
        </div>
    </section>

    {{-- Metrics --}}
    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Reservation summary">
        @foreach($metrics as $metric)
            <article class="dash-card dash-card-hover p-3.5 sm:p-4">
                <div class="flex items-start justify-between gap-2">
                    <p class="truncate text-[11px] font-semibold uppercase tracking-[.08em] text-slate-400">{{ $metric['label'] }}</p>
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg ring-1 {{ $tones[$metric['tone']] }}">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $metric['icon'] }}"/></svg>
                    </span>
                </div>
                <p class="mt-2 truncate text-xl font-bold tracking-tight text-white sm:text-2xl">{{ $metric['value'] }}</p>
                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $metric['note'] }}</p>
            </article>
        @endforeach
    </section>

    {{-- Next session + quick booking --}}
    <section class="grid items-start gap-4 sm:gap-5 xl:grid-cols-[1.6fr_.8fr]">

        {{-- Next session --}}
        @if($nextBooking)
            <article class="dash-card dash-card-next overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/[.06] px-4 py-2.5 sm:px-5">
                    <p class="dash-eyebrow">Your next session</p>
                    <x-reservation-status :status="$nextBooking->status->value"/>
                </div>

                <div class="flex flex-col gap-3 p-4">
                    <div class="flex items-start gap-3">
                        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-lime-300 text-[#15200c] shadow-lg shadow-lime-300/10">
                            <span class="text-base font-bold leading-none">{{ $nextBooking->starts_at->format('j') }}</span>
                            <span class="mt-0.5 text-[9px] font-bold uppercase tracking-[.14em]">{{ $nextBooking->starts_at->format('M') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-base font-bold text-white sm:text-lg">{{ $nextBooking->court?->name ?? 'Court unavailable' }}</h2>
                            <p class="mt-0.5 text-xs text-slate-400 sm:text-[13px]">
                                {{ $nextBooking->starts_at->format('D, M j, Y') }} ·
                                {{ $nextBooking->starts_at->format('g:i A') }}–{{ $nextBooking->ends_at->format('g:i A') }}
                            </p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-500">{{ $nextBooking->reference_number }}</p>
                        </div>
                    </div>

                    {{-- Countdown + reminder --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <div x-data="sessionCountdown"
                             data-starts-at="{{ $nextBooking->starts_at->toIso8601String() }}"
                             x-init="start()"
                             class="dash-chip dash-chip-count">
                            <svg class="h-3.5 w-3.5 shrink-0 text-lime-300" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0Z"/></svg>
                            <span class="whitespace-nowrap text-[10px] font-bold uppercase tracking-[.12em] text-slate-500">Starts in</span>
                            <span class="whitespace-nowrap font-mono text-xs font-bold tabular-nums text-lime-300" x-text="label">—</span>
                        </div>

                        <div x-data="bookingReminder"
                             data-booking-id="{{ $nextBooking->id }}"
                             data-starts-at="{{ $nextBooking->starts_at->toIso8601String() }}"
                             data-court="{{ $nextBooking->court?->name }}"
                             x-init="start()"
                             class="relative">
                            <button type="button" x-on:click="open = ! open"
                                    class="dash-chip dash-chip-button">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
                                <span class="whitespace-nowrap" x-text="setLabel">Set reminder</span>
                            </button>

                            <div x-show="open" x-cloak x-on:click.outside="open = false"
                                 class="absolute left-0 z-30 mt-2 w-52 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-white/10 bg-[#0b1119] p-1 shadow-2xl shadow-black/60">
                                <p class="px-2 py-1.5 text-[10px] font-bold uppercase tracking-[.16em] text-slate-500">Remind me before</p>
                                <template x-for="option in options" :key="option.minutes">
                                    <button type="button" x-on:click="set(option)"
                                            class="flex w-full items-center justify-between rounded-lg px-2 py-1.5 text-left text-xs font-semibold text-slate-300 transition hover:bg-white/5 hover:text-white">
                                        <span x-text="option.label"></span>
                                        <svg x-show="lead === option.minutes" class="h-3.5 w-3.5 text-lime-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                                <button type="button" x-show="lead !== null" x-on:click="remove()"
                                        class="mt-1 w-full rounded-lg border-t border-white/[.06] px-2 pt-2 pb-1.5 text-left text-xs font-semibold text-rose-300 transition hover:bg-rose-500/10">
                                    Turn reminder off
                                </button>
                            </div>

                            <div x-show="toast" x-cloak x-transition.opacity.duration.300ms
                                 class="fixed inset-x-4 bottom-4 z-50 flex items-start gap-2.5 rounded-xl border border-lime-300/30 bg-[#0b1119] p-3 shadow-2xl shadow-black/60 sm:inset-x-auto sm:right-4 sm:bottom-4 sm:max-w-xs" role="status">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-lime-300/10 text-lime-300 ring-1 ring-lime-300/25">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-bold uppercase tracking-[.16em] text-lime-300">Reminder</p>
                                    <p class="mt-0.5 text-xs leading-5 text-slate-300" x-text="toast"></p>
                                </div>
                                <button type="button" x-on:click="toast = ''" class="shrink-0 rounded-md p-1 text-slate-500 transition hover:text-white" aria-label="Dismiss reminder">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                </button>
                            </div>
                        </div>

                        <a href="{{ route('customer.reservations.show', $nextBooking) }}" class="dash-btn-ghost ml-auto">
                            View details <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    @if($nextBooking->status->value === 'pending')
                        <p class="rounded-lg border border-amber-300/15 bg-amber-300/5 px-3 py-2 text-[11px] text-amber-200">
                            Payment is pending. This slot is held until {{ $nextBooking->payment_deadline?->format('g:i A') }}.
                        </p>
                    @endif
                </div>
            </article>
        @else
            <article class="dash-card flex flex-col items-center justify-center gap-2 p-8 text-center">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/5 text-slate-400 ring-1 ring-white/10">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z"/></svg>
                </span>
                <h2 class="text-sm font-bold text-white">No upcoming session</h2>
                <p class="max-w-xs text-xs text-slate-400">Book a court and your next game will show up here.</p>
                @if($courts->isNotEmpty())
                    <a href="{{ route('customer.availability.index', ['court' => $courts->first()]) }}" class="dash-btn-primary mt-2">Find a time</a>
                @endif
            </article>
        @endif

        {{-- Court quick picker --}}
        <aside class="dash-card overflow-hidden">
            <header class="flex items-center justify-between gap-2 border-b border-white/[.06] px-4 py-2.5">
                <p class="dash-eyebrow">Quick booking</p>
                <span class="text-[10px] font-semibold text-slate-500">{{ $courts->count() }} courts</span>
            </header>
            <div class="divide-y divide-white/[.05]">
                @forelse($courts as $court)
                    <a href="{{ route('customer.availability.index', ['court' => $court]) }}" class="group flex items-center gap-3 px-3 py-2.5 transition hover:bg-white/[.03]">
                        <x-court-photo :court="$court" class="h-9 w-11 shrink-0 rounded-lg"/>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-semibold text-white group-hover:text-lime-300">{{ $court->name }}</span>
                            <span class="block truncate text-[11px] text-slate-500">₱{{ number_format((float) $court->hourly_rate, 2) }} / hour</span>
                        </span>
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-white/5 text-[11px] text-slate-400 transition group-hover:bg-lime-300 group-hover:text-[#15200c]">→</span>
                    </a>
                @empty
                    <p class="px-4 py-8 text-center text-xs text-slate-400">No active courts are available.</p>
                @endforelse
            </div>
        </aside>
    </section>

    {{-- Upcoming reservations --}}
    <section class="dash-card overflow-hidden">
        <header class="flex items-center justify-between gap-2 border-b border-white/[.06] px-4 py-2.5 sm:px-5">
            <div class="flex items-baseline gap-2">
                <p class="dash-eyebrow">Schedule</p>
                <h2 class="text-sm font-bold text-white">Upcoming reservations</h2>
            </div>
            <a href="{{ route('customer.reservations.index') }}" class="shrink-0 text-[11px] font-semibold text-slate-400 transition hover:text-lime-300">View all →</a>
        </header>

        @if($upcomingBookings->isEmpty())
            <div class="px-5 py-10 text-center">
                <h3 class="text-sm font-semibold text-white">Your schedule is clear</h3>
                <p class="mt-1 text-xs text-slate-400">Book a court and your sessions will appear here.</p>
            </div>
        @else
            <ul class="divide-y divide-white/[.05]">
                @foreach($upcomingBookings as $booking)
                    <li>
                        <a href="{{ route('customer.reservations.show', $booking) }}" class="group flex items-center gap-3 px-3 py-2.5 transition hover:bg-white/[.03] sm:px-5">
                            <div class="w-9 shrink-0 text-center">
                                <p class="text-base font-bold leading-none text-white">{{ $booking->starts_at->format('j') }}</p>
                                <p class="mt-0.5 text-[9px] font-bold uppercase tracking-[.12em] text-slate-500">{{ $booking->starts_at->format('M') }}</p>
                            </div>
                            <div class="h-8 w-px shrink-0 bg-white/[.07]"></div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-white group-hover:text-lime-300 sm:text-[13px]">{{ $booking->court?->name ?? 'Court unavailable' }}</p>
                                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $booking->starts_at->format('D, g:i A') }} – {{ $booking->ends_at->format('g:i A') }}</p>
                            </div>
                            <span class="hidden shrink-0 text-[10px] font-semibold text-lime-300/70 sm:block">{{ $booking->starts_at->diffForHumans(['parts' => 1, 'short' => true]) }}</span>
                            <span class="shrink-0"><x-reservation-status :status="$booking->status->value"/></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Policies and rules --}}
    @php
        $adminFee = (float) config('reservation.refund.admin_fee_percent');
        $policies = [
            [
                'title' => 'Refund of an online payment',
                'icon' => 'M16 15v-1a4 4 0 00-4-4H8m0 0 3-3m-3 3 3 3M3 12a9 9 0 1018 0 9 9 0 00-18 0Z',
                'tone' => 'sky',
                'points' => [
                    'Ask for a refund at least ' . config('reservation.refund.request_deadline_hours') . ' hours before your slot.',
                    'A ' . rtrim(rtrim(number_format($adminFee, 2), '0'), '.') . '% administration fee is deducted from what you paid.',
                    'Example: paid ₱1,000.00, then ₱' . number_format(1000 * $adminFee / 100, 2) . ' is kept and ₱' . number_format(1000 - (1000 * $adminFee / 100), 2) . ' comes back to you.',
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
                    'Each no-show counts for ' . config('reservation.no_show.counting_window_days') . ' days.',
                    'After ' . config('reservation.no_show.limit_before_suspension') . ' no-shows, management may suspend your account.',
                    'Cancel early if you cannot come — a cancelled booking is not a no-show.',
                ],
            ],
            [
                'title' => 'Payment and arrival',
                'icon' => 'M4 7h16v10H4V7Zm0 4h16',
                'tone' => 'lime',
                'points' => [
                    'Online payment holds the slot for ' . config('reservation.hold_minutes.online') . ' minutes.',
                    'On-site payment holds it until the day of play, but never past the start time.',
                    'A booking is confirmed only after the payment is verified.',
                    'We email you a reminder ' . config('reservation.reminder.minutes_before') . ' minutes before your slot starts.',
                    'Present your reference number or email receipt at court management.',
                ],
            ],
        ];
    @endphp

    <section aria-label="Policies and rules" class="dash-card overflow-hidden">
        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-white/[.06] px-4 py-2.5 sm:px-5">
            <div class="flex items-baseline gap-2">
                <p class="dash-eyebrow">Policies</p>
                <h2 class="text-sm font-bold text-white">Know before you book</h2>
            </div>
            <a href="{{ route('terms') }}" class="shrink-0 text-[11px] font-semibold text-slate-400 transition hover:text-lime-300">Read full terms →</a>
        </header>
        <div class="grid grid-cols-1 gap-px bg-white/[.05] md:grid-cols-3">
            @foreach($policies as $policy)
                @php
                    $tone = match($policy['tone']) {
                        'amber' => 'bg-amber-400/10 text-amber-300 ring-amber-400/25',
                        'sky' => 'bg-sky-400/10 text-sky-300 ring-sky-400/25',
                        default => 'bg-lime-300/10 text-lime-300 ring-lime-300/25',
                    };
                @endphp
                <article class="bg-[#0f1622] p-4">
                    <div class="flex items-center gap-2">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md ring-1 {{ $tone }}">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $policy['icon'] }}"/></svg>
                        </span>
                        <h3 class="text-[12px] font-bold leading-snug text-white">{{ $policy['title'] }}</h3>
                    </div>
                    <ul class="mt-2.5 space-y-1 border-t border-white/[.05] pt-2.5 text-[11px] leading-[1.6] text-slate-400">
                        @foreach($policy['points'] as $point)
                            <li class="flex gap-1.5"><span class="mt-[5px] h-[3px] w-[3px] shrink-0 rounded-full bg-slate-600"></span><span>{{ $point }}</span></li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const STORE = 'pickleball-reminders';

        const readAll = () => {
            try { return JSON.parse(localStorage.getItem(STORE) || '{}'); } catch (e) { return {}; }
        };

        const writeAll = (all) => {
            try { localStorage.setItem(STORE, JSON.stringify(all)); } catch (e) { /* ignore */ }
        };

        const fireReminder = (record) => {
            const when = new Date(record.startsAt).toLocaleString([], { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
            const body = 'You booked ' + record.court + ' for ' + when + '. See you at the court!';

            if (window.Notification && Notification.permission === 'granted') {
                try { new Notification('Pickleball reminder', { body: body, tag: 'booking-' + record.id }); } catch (e) { /* ignore */ }
            }

            window.dispatchEvent(new CustomEvent('booking-reminder-fired', { detail: { message: body } }));
        };

        const checkReminders = () => {
            const all = readAll();
            const now = Date.now();
            let changed = false;

            Object.keys(all).forEach((id) => {
                const record = all[id];
                if (! record || record.fired) { return; }

                const dueAt = new Date(record.startsAt).getTime() - record.lead * 60000;
                const endsAt = new Date(record.startsAt).getTime() + 3600000; // stop nagging an hour after the slot started

                if (now >= dueAt && now <= endsAt) {
                    record.fired = true;
                    changed = true;
                    fireReminder(record);
                }
            });

            if (changed) { writeAll(all); }
        };

        document.addEventListener('alpine:init', () => {
            Alpine.data('sessionCountdown', () => ({
                remaining: 0,
                timer: null,

                start() {
                    const target = new Date(this.$el.dataset.startsAt).getTime();
                    const tick = () => { this.remaining = Math.max(0, target - Date.now()); };
                    tick();
                    this.timer = setInterval(tick, 1000);
                },

                get label() {
                    const total = Math.floor(this.remaining / 1000);
                    if (total <= 0) { return 'Starting now'; }

                    const days = Math.floor(total / 86400);
                    const hours = Math.floor((total % 86400) / 3600);
                    const minutes = Math.floor((total % 3600) / 60);
                    const seconds = total % 60;
                    const pad = (value) => String(value).padStart(2, '0');

                    if (days > 0) { return days + 'd ' + pad(hours) + 'h ' + pad(minutes) + 'm'; }
                    if (hours > 0) { return hours + 'h ' + pad(minutes) + 'm ' + pad(seconds) + 's'; }
                    return minutes + 'm ' + pad(seconds) + 's';
                },
            }));

            Alpine.data('bookingReminder', () => ({
                open: false,
                lead: null,
                id: null,
                court: '',
                startsAt: '',
                toast: '',
                options: [
                    { label: '1 hour before', minutes: 60 },
                    { label: '3 hours before', minutes: 180 },
                    { label: '1 day before', minutes: 1440 },
                ],

                start() {
                    this.id = this.$el.dataset.bookingId;
                    this.court = this.$el.dataset.court || 'your court';
                    this.startsAt = this.$el.dataset.startsAt;

                    const record = readAll()[this.id];
                    this.lead = record ? record.lead : null;

                    window.addEventListener('booking-reminder-fired', (event) => {
                        this.toast = event.detail.message;
                        setTimeout(() => { this.toast = ''; }, 12000);
                    });
                },

                get setLabel() {
                    if (this.lead === null) { return 'Set reminder'; }
                    const option = this.options.find((item) => item.minutes === this.lead);
                    return option ? 'Reminder: ' + option.label : 'Reminder set';
                },

                set(option) {
                    const all = readAll();
                    all[this.id] = {
                        id: this.id,
                        court: this.court,
                        startsAt: this.startsAt,
                        lead: option.minutes,
                        fired: false,
                    };
                    writeAll(all);
                    this.lead = option.minutes;
                    this.open = false;

                    if (window.Notification && Notification.permission === 'default') {
                        Notification.requestPermission();
                    }

                    this.toast = 'Reminder set: ' + option.label + '. Keep this page open to get the alert.';
                    setTimeout(() => { this.toast = ''; }, 9000);
                },

                remove() {
                    const all = readAll();
                    delete all[this.id];
                    writeAll(all);
                    this.lead = null;
                    this.open = false;
                },
            }));
        });

        checkReminders();
        setInterval(checkReminders, 30000);
    })();
</script>
@endpush
