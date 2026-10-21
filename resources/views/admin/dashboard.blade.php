@extends('layouts.admin')

@section('content')
<div class="admin-dashboard space-y-6">
    {{-- Operations hero --}}
    <header class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#111827] shadow-2xl shadow-black/20">
        <img src="{{ asset('images/courts/featured-pickleball.png') }}" alt="" class="absolute inset-y-0 right-0 hidden h-full w-[46%] object-cover opacity-30 saturate-50 lg:block">
        <div class="absolute inset-0 bg-[linear-gradient(90deg,#111827_0%,#111827_57%,rgba(17,24,39,.6)_100%)]"></div>
        <div class="absolute -left-20 -top-24 h-60 w-60 rounded-full bg-lime-300/10 blur-3xl"></div>

        <div class="relative flex min-h-64 flex-col justify-between gap-8 p-6 sm:p-8 lg:flex-row lg:items-end lg:p-10">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-lime-300/20 bg-lime-300/10 px-3 py-1 text-xs font-bold uppercase tracking-[.16em] text-lime-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-lime-300"></span>
                    Operations center
                </div>
                <h1 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    Facility overview
                </h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-400 sm:text-base">
                    Monitor today’s schedule, verify payments, and keep every court ready for play.
                </p>
                <p class="mt-5 flex items-center gap-2 text-sm text-slate-500">
                    <svg class="h-4 w-4 text-lime-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z"/></svg>
                    {{ now(config('reservation.timezone'))->format('l, F j, Y') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.reservations.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/10">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14a2 2 0 012 2v14H5a2 2 0 01-2-2V6a2 2 0 012-2Zm4 4h6m-6 4h6m-6 4h4"/></svg>
                    Reservations
                </a>
                <a href="{{ route('admin.courts.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-lime-300 px-4 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-lime-300/10 transition hover:-translate-y-0.5 hover:bg-lime-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                    Add court
                </a>
            </div>
        </div>
    </header>

    {{-- Operational metrics --}}
    @php
        $metrics = [
            ['label' => 'Today’s bookings', 'value' => $todayCount, 'note' => $confirmedCount.' confirmed · '.$pendingCount.' pending', 'tone' => 'lime', 'icon' => 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z'],
            ['label' => 'Total collected', 'value' => '₱'.number_format((float) $collectedTotal, 2), 'note' => '₱'.number_format((float) $revenueThisMonth, 2).' this month', 'tone' => 'sky', 'icon' => 'M4 7h16v10H4V7Zm0 4h16m-5 3h2'],
            ['label' => 'Active courts', 'value' => $activeCourts, 'note' => $totalCourts.' courts in inventory', 'tone' => 'violet', 'icon' => 'M4 6h16M4 12h16M4 18h16'],
            ['label' => 'Customers', 'value' => $customerCount, 'note' => 'Registered player accounts', 'tone' => 'amber', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m7-10a4 4 0 100-8 4 4 0 000 8Zm13 10v-2a4 4 0 00-3-3.87m-1-7.26a4 4 0 010 7.75'],
        ];
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Business summary">
        @foreach($metrics as $metric)
            @php
                $tone = match($metric['tone']) {
                    'sky' => 'bg-sky-400/10 text-sky-300 ring-sky-400/20',
                    'violet' => 'bg-violet-400/10 text-violet-300 ring-violet-400/20',
                    'amber' => 'bg-amber-400/10 text-amber-300 ring-amber-400/20',
                    default => 'bg-lime-300/10 text-lime-300 ring-lime-300/20',
                };
            @endphp
            <article class="group rounded-2xl border border-white/10 bg-[#111827] p-5 shadow-xl shadow-black/10 transition hover:-translate-y-0.5 hover:border-white/20">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-400">{{ $metric['label'] }}</p>
                        <p class="mt-3 truncate text-3xl font-bold tracking-tight text-white">{{ $metric['value'] }}</p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl ring-1 {{ $tone }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $metric['icon'] }}"/></svg>
                    </span>
                </div>
                <p class="mt-4 text-xs text-slate-500">{{ $metric['note'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.55fr_.75fr]">
        <div class="space-y-6">
            {{-- Today's schedule --}}
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#111827] shadow-xl shadow-black/10">
                <header class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-5 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-lime-300">Live operations</p>
                        <h2 class="mt-1 text-lg font-bold text-white">Today’s schedule</h2>
                        <p class="mt-1 text-xs text-slate-500">Pending and confirmed court activity</p>
                    </div>
                    <a href="{{ route('admin.reservations.index', ['date' => now(config('reservation.timezone'))->toDateString()]) }}" class="text-sm font-semibold text-slate-300 transition hover:text-lime-300">Full schedule →</a>
                </header>

                @forelse($todaysReservations as $reservation)
                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="group flex items-center gap-4 border-b border-white/10 px-5 py-4 transition last:border-0 hover:bg-white/[.035] sm:px-6">
                        <div class="w-20 shrink-0">
                            <p class="font-bold text-white">{{ $reservation->starts_at->format('g:i A') }}</p>
                            <p class="mt-1 text-xs text-slate-500">to {{ $reservation->ends_at->format('g:i A') }}</p>
                        </div>
                        <span class="h-10 w-1 rounded-full {{ $reservation->status->value === 'confirmed' ? 'bg-lime-300' : 'bg-amber-300' }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white group-hover:text-lime-300">{{ $reservation->court?->name ?? 'Court unavailable' }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $reservation->user?->name ?? 'Customer unavailable' }} · {{ $reservation->reference_number }}</p>
                        </div>
                        <x-reservation-status :status="$reservation->status->value"/>
                        <svg class="hidden h-4 w-4 text-slate-600 transition group-hover:translate-x-1 group-hover:text-lime-300 sm:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                    </a>
                @empty
                    <div class="px-6 py-14 text-center">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-white/5 text-slate-400 ring-1 ring-white/10">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2Z"/></svg>
                        </span>
                        <h3 class="mt-4 font-semibold text-white">No bookings today</h3>
                        <p class="mt-1 text-sm text-slate-500">The facility schedule is currently clear.</p>
                    </div>
                @endforelse
            </section>

            {{-- Court inventory --}}
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#111827] shadow-xl shadow-black/10">
                <header class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-5 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-lime-300">Inventory</p>
                        <h2 class="mt-1 text-lg font-bold text-white">Court readiness</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $activeCourts }} of {{ $totalCourts }} courts currently active</p>
                    </div>
                    <a href="{{ route('admin.courts.index') }}" class="text-sm font-semibold text-slate-300 transition hover:text-lime-300">Manage courts →</a>
                </header>
                <div class="grid gap-px bg-white/10 sm:grid-cols-2">
                    @forelse($courts as $court)
                        <a href="{{ route('admin.courts.show', $court) }}" class="group flex items-center gap-3 bg-[#111827] p-4 transition hover:bg-[#182131]">
                            <x-court-photo :court="$court" class="h-12 w-14 shrink-0 rounded-lg"/>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-white group-hover:text-lime-300">{{ $court->name }}</span>
                                <span class="mt-1 block text-xs text-slate-500">₱{{ number_format((float) $court->hourly_rate, 2) }} / hour</span>
                            </span>
                            @if($court->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-lime-300/10 px-2.5 py-1 text-[11px] font-bold text-lime-300 ring-1 ring-lime-300/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-lime-300"></span> Active
                                </span>
                            @else
                                <span class="rounded-full bg-slate-700/50 px-2.5 py-1 text-[11px] font-bold text-slate-400">Inactive</span>
                            @endif
                        </a>
                    @empty
                        <p class="bg-[#111827] p-8 text-sm text-slate-500">No courts have been added.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            {{-- Action queue --}}
            <section class="overflow-hidden rounded-2xl border {{ $onSitePending->isEmpty() ? 'border-white/10' : 'border-amber-300/25' }} bg-[#111827] shadow-xl shadow-black/10">
                <header class="flex items-center justify-between border-b border-white/10 px-5 py-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.16em] {{ $onSitePending->isEmpty() ? 'text-lime-300' : 'text-amber-300' }}">Action queue</p>
                        <h2 class="mt-1 text-lg font-bold text-white">Needs attention</h2>
                    </div>
                    @if($onSitePending->isNotEmpty())
                        <span class="grid h-8 min-w-8 place-items-center rounded-full bg-amber-300 px-2 text-xs font-bold text-slate-950">{{ $onSitePending->count() }}</span>
                    @endif
                </header>

                @if($onSitePending->isEmpty())
                    <div class="px-5 py-10 text-center">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-lime-300/10 text-lime-300 ring-1 ring-lime-300/20">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        </span>
                        <p class="mt-4 font-semibold text-white">You’re all caught up</p>
                        <p class="mt-1 text-sm text-slate-500">No on-site payments require verification.</p>
                    </div>
                @else
                    <ul class="divide-y divide-white/10">
                        @foreach($onSitePending as $payment)
                            <li class="px-5 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-white">{{ $payment->reservation?->court?->name ?? 'Court unavailable' }}</p>
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ $payment->reservation?->user?->name ?? 'Customer unavailable' }}</p>
                                    </div>
                                    <p class="shrink-0 text-sm font-bold text-amber-300">₱{{ number_format((float) $payment->amount, 2) }}</p>
                                </div>
                                @if($payment->reservation?->payment_deadline)
                                    <p class="mt-2 text-[11px] text-slate-500">Due {{ $payment->reservation->payment_deadline->diffForHumans() }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-white/10 p-3">
                        <a href="{{ route('admin.payments.index') }}" class="flex items-center justify-center rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-amber-200">Review payments</a>
                    </div>
                @endif
            </section>

            {{-- Recent activity --}}
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-[#111827] shadow-xl shadow-black/10">
                <header class="border-b border-white/10 px-5 py-5">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-lime-300">Audit trail</p>
                    <h2 class="mt-1 text-lg font-bold text-white">Recent activity</h2>
                </header>
                @forelse($recentActivity as $log)
                    <div class="flex gap-3 border-b border-white/10 px-5 py-4 last:border-0">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-lime-300 ring-4 ring-lime-300/10"></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-white">{{ ucfirst(str_replace('_', ' ', last(explode('.', $log->action)))) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">No recent activity to display.</p>
                @endforelse
            </section>

            {{-- Quick links --}}
            <section class="rounded-2xl border border-white/10 bg-[#111827] p-5 shadow-xl shadow-black/10">
                <p class="text-xs font-bold uppercase tracking-[.16em] text-lime-300">Quick actions</p>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('admin.court-blocks.index') }}" class="rounded-xl border border-white/10 bg-white/[.03] px-3 py-3 text-center text-xs font-semibold text-slate-300 transition hover:border-lime-300/30 hover:text-lime-300">Court blocks</a>
                    <a href="{{ route('admin.reports.index') }}" class="rounded-xl border border-white/10 bg-white/[.03] px-3 py-3 text-center text-xs font-semibold text-slate-300 transition hover:border-lime-300/30 hover:text-lime-300">View reports</a>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
