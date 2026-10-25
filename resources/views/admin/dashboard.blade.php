@extends('layouts.admin')

@section('content')
@php
    $metrics = [
        ['label' => 'Today’s bookings', 'value' => $todayCount, 'note' => $confirmedCount . ' confirmed · ' . $pendingCount . ' pending', 'tone' => 'lime', 'icon' => 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z'],
        ['label' => 'Total collected', 'value' => '₱' . number_format((float) $collectedTotal, 2), 'note' => '₱' . number_format((float) $revenueThisMonth, 2) . ' this month', 'tone' => 'sky', 'icon' => 'M4 7h16v10H4V7Zm0 4h16m-5 3h2'],
        ['label' => 'Active courts', 'value' => $activeCourts, 'note' => $totalCourts . ' courts in inventory', 'tone' => 'violet', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
        ['label' => 'Customers', 'value' => $customerCount, 'note' => 'Registered player accounts', 'tone' => 'amber', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8Zm13 10v-2a4 4 0 00-3-3.87m-1-7.26a4 4 0 010 7.75'],
    ];
    $tones = [
        'sky' => 'bg-sky-400/10 text-sky-300 ring-sky-400/20',
        'violet' => 'bg-violet-400/10 text-violet-300 ring-violet-400/20',
        'amber' => 'bg-amber-400/10 text-amber-300 ring-amber-400/20',
        'lime' => 'bg-lime-300/10 text-lime-300 ring-lime-300/20',
    ];
@endphp

<div class="dash space-y-4 sm:space-y-5">

    {{-- Greeting bar --}}
    <section class="dash-card flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-5">
        <div class="min-w-0">
            <p class="dash-eyebrow">Operations center</p>
            <h1 class="mt-1 truncate text-lg font-bold tracking-tight text-white sm:text-xl">Facility overview</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="dash-chip">
                <svg class="h-3.5 w-3.5 text-lime-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z"/></svg>
                {{ now(config('reservation.timezone'))->format('D, M j, Y') }}
            </span>
            <a href="{{ route('admin.reservations.index') }}" class="dash-btn-ghost">Reservations</a>
            <a href="{{ route('admin.courts.create') }}" class="dash-btn-primary">
                Add court
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
            </a>
        </div>
    </section>

    {{-- Metrics --}}
    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Business summary">
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

    <div class="grid gap-4 sm:gap-5 xl:grid-cols-[1.6fr_.8fr]">
        <div class="space-y-4 sm:space-y-5">
            {{-- Today's schedule --}}
            <section class="dash-card overflow-hidden">
                <header class="flex items-center justify-between gap-2 border-b border-white/[.06] px-4 py-2.5 sm:px-5">
                    <div class="flex items-baseline gap-2">
                        <p class="dash-eyebrow">Live operations</p>
                        <h2 class="text-sm font-bold text-white">Today’s schedule</h2>
                    </div>
                    <a href="{{ route('admin.reservations.index', ['date' => now(config('reservation.timezone'))->toDateString()]) }}" class="shrink-0 text-[11px] font-semibold text-slate-400 transition hover:text-lime-300">Full schedule →</a>
                </header>

                @forelse($todaysReservations as $reservation)
                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="group flex items-center gap-3 border-b border-white/[.05] px-3 py-2.5 transition last:border-0 hover:bg-white/[.03] sm:px-5">
                        <div class="w-16 shrink-0">
                            <p class="text-xs font-bold text-white">{{ $reservation->starts_at->format('g:i A') }}</p>
                            <p class="mt-0.5 text-[10px] text-slate-500">to {{ $reservation->ends_at->format('g:i A') }}</p>
                        </div>
                        <span class="h-8 w-[3px] shrink-0 rounded-full {{ $reservation->status->value === 'confirmed' ? 'bg-lime-300' : 'bg-amber-300' }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-white group-hover:text-lime-300 sm:text-[13px]">{{ $reservation->court?->name ?? 'Court unavailable' }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $reservation->user?->name ?? 'Customer unavailable' }} · {{ $reservation->reference_number }}</p>
                        </div>
                        <span class="shrink-0"><x-reservation-status :status="$reservation->status->value"/></span>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center">
                        <h3 class="text-sm font-semibold text-white">No bookings today</h3>
                        <p class="mt-1 text-xs text-slate-400">The facility schedule is currently clear.</p>
                    </div>
                @endforelse
            </section>

            {{-- Court inventory --}}
            <section class="dash-card overflow-hidden">
                <header class="flex items-center justify-between gap-2 border-b border-white/[.06] px-4 py-2.5 sm:px-5">
                    <div class="flex items-baseline gap-2">
                        <p class="dash-eyebrow">Inventory</p>
                        <h2 class="text-sm font-bold text-white">Court readiness</h2>
                    </div>
                    <a href="{{ route('admin.courts.index') }}" class="shrink-0 text-[11px] font-semibold text-slate-400 transition hover:text-lime-300">Manage courts →</a>
                </header>
                <div class="grid gap-px bg-white/[.05] sm:grid-cols-2">
                    @forelse($courts as $court)
                        <a href="{{ route('admin.courts.show', $court) }}" class="group flex items-center gap-3 bg-[#0f1622] p-3 transition hover:bg-white/[.04]">
                            <x-court-photo :court="$court" class="h-9 w-11 shrink-0 rounded-lg"/>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-xs font-semibold text-white group-hover:text-lime-300">{{ $court->name }}</span>
                                <span class="block truncate text-[11px] text-slate-500">₱{{ number_format((float) $court->hourly_rate, 2) }} / hour</span>
                            </span>
                            @if($court->is_active)
                                <span class="shrink-0 rounded-full bg-lime-300/10 px-2 py-0.5 text-[10px] font-bold text-lime-300 ring-1 ring-lime-300/20">Active</span>
                            @else
                                <span class="shrink-0 rounded-full bg-slate-700/50 px-2 py-0.5 text-[10px] font-bold text-slate-400">Inactive</span>
                            @endif
                        </a>
                    @empty
                        <p class="bg-[#0f1622] p-6 text-xs text-slate-400">No courts have been added.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-4 sm:space-y-5">
            {{-- Action queue --}}
            <section class="dash-card overflow-hidden {{ $onSitePending->isEmpty() ? '' : 'border-amber-300/25' }}">
                <header class="flex items-center justify-between gap-2 border-b border-white/[.06] px-4 py-2.5">
                    <div class="flex items-baseline gap-2">
                        <p class="dash-eyebrow {{ $onSitePending->isEmpty() ? '' : 'text-amber-300' }}">Action queue</p>
                        <h2 class="text-sm font-bold text-white">Needs attention</h2>
                    </div>
                    @if($onSitePending->isNotEmpty())
                        <span class="grid h-5 min-w-5 place-items-center rounded-full bg-amber-300 px-1.5 text-[10px] font-bold text-[#1a1508]">{{ $onSitePending->count() }}</span>
                    @endif
                </header>

                @if($onSitePending->isEmpty())
                    <div class="flex flex-col items-center gap-1.5 px-4 py-7 text-center">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-lime-300/10 text-lime-300 ring-1 ring-lime-300/20">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <p class="mt-1 text-xs font-semibold text-white">You’re all caught up</p>
                        <p class="text-[11px] text-slate-400">No on-site payments require verification.</p>
                    </div>
                @else
                    <ul class="divide-y divide-white/[.05]">
                        @foreach($onSitePending as $payment)
                            <li class="px-4 py-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-semibold text-white">{{ $payment->reservation?->court?->name ?? 'Court unavailable' }}</p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $payment->reservation?->user?->name ?? 'Customer unavailable' }}</p>
                                    </div>
                                    <p class="shrink-0 text-xs font-bold text-amber-300">₱{{ number_format((float) $payment->amount, 2) }}</p>
                                </div>
                                @if($payment->reservation?->payment_deadline)
                                    <p class="mt-1 text-[10px] text-slate-500">Due {{ $payment->reservation->payment_deadline->diffForHumans() }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-white/[.06] p-2.5">
                        <a href="{{ route('admin.payments.index') }}" class="dash-btn-amber w-full">Review payments</a>
                    </div>
                @endif
            </section>

            {{-- Recent activity --}}
            <section class="dash-card overflow-hidden">
                <header class="border-b border-white/[.06] px-4 py-2.5">
                    <p class="dash-eyebrow">Audit trail</p>
                    <h2 class="mt-0.5 text-sm font-bold text-white">Recent activity</h2>
                </header>
                @forelse($recentActivity as $log)
                    <div class="flex gap-2.5 border-b border-white/[.05] px-4 py-2.5 last:border-0">
                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-lime-300 ring-[3px] ring-lime-300/10"></span>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-medium text-white">{{ ucfirst(str_replace('_', ' ', last(explode('.', $log->action)))) }}</p>
                            <p class="mt-0.5 truncate text-[10px] text-slate-500">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-xs text-slate-400">No recent activity to display.</p>
                @endforelse
            </section>

            {{-- Quick links --}}
            <section class="dash-card p-3">
                <p class="dash-eyebrow px-1">Quick actions</p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <a href="{{ route('admin.court-blocks.index') }}" class="rounded-lg border border-white/[.07] bg-white/[.03] px-2 py-2 text-center text-[11px] font-semibold text-slate-300 transition hover:border-lime-300/30 hover:text-lime-300">Court blocks</a>
                    <a href="{{ route('admin.reports.index') }}" class="rounded-lg border border-white/[.07] bg-white/[.03] px-2 py-2 text-center text-[11px] font-semibold text-slate-300 transition hover:border-lime-300/30 hover:text-lime-300">View reports</a>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
