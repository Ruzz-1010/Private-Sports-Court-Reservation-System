@extends('layouts.admin')

@section('content')

{{-- Header --}}
<div class="motion-reveal relative mb-8 flex flex-wrap items-end justify-between gap-4 overflow-hidden rounded-[2rem] bg-slate-950 px-7 py-8 text-white shadow-xl shadow-slate-300 sm:px-9">
    <div class="absolute inset-0 -z-0 bg-[radial-gradient(circle_at_top_right,_rgba(20,184,166,0.3),transparent_28%),radial-gradient(circle_at_10%_100%,_rgba(79,70,229,0.4),transparent_34%)]"></div>
    <div class="relative z-10">
    <p class="text-sm font-semibold text-teal-200">
        {{ now(config('reservation.timezone'))->format('l, F j, Y') }}
    </p>
    <h1 class="mt-1 text-3xl font-black tracking-tight text-white">Facility overview</h1>
    <p class="mt-2 text-sm text-slate-300">A clear view of today’s courts, bookings, and collections.</p>
    </div>
    <div class="relative z-10 flex gap-2"><a href="{{ route('admin.reservations.index') }}" class="rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur hover:bg-white/15">Reservations</a><a href="{{ route('admin.courts.create') }}" class="rounded-xl bg-teal-300 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-lg shadow-teal-500/20 hover:bg-teal-200">Add court</a></div>
</div>

{{-- Stat tiles --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-600">Today's bookings</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $todayCount }}</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/>
                </svg>
            </span>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ $confirmedCount }} confirmed &middot; {{ $pendingCount }} pending</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-600">Collected (all time)</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">₱{{ number_format($collectedTotal, 2) }}</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
        </div>
        <p class="mt-3 text-xs text-slate-500">₱{{ number_format($revenueThisMonth, 2) }} this month</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-600">Active courts</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $activeCourts }}</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </span>
        </div>
        <a href="{{ route('admin.courts.index') }}" class="mt-3 inline-block text-xs font-medium text-slate-600 hover:text-slate-900">
            Manage courts &rarr;
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-600">Registered customers</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $customerCount }}</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ $totalCourts }} courts total</p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">

    {{-- Today's schedule --}}
    <div class="lg:col-span-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Today's schedule</h2>
                <a href="{{ route('admin.reservations.index', ['date' => now(config('reservation.timezone'))->toDateString()]) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">View all today</a>
            </div>

            @if ($todaysReservations->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-600">No bookings scheduled today.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($todaysReservations as $reservation)
                        <li class="flex items-center gap-4 px-5 py-3">
                            <div class="w-24 shrink-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $reservation->starts_at->format('g:i A') }}</p>
                                <p class="text-xs text-slate-500">{{ $reservation->ends_at->format('g:i A') }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $reservation->court->name }}</p>
                                <p class="truncate text-xs text-slate-600">{{ $reservation->user->name }} &middot; {{ $reservation->reference_number }}</p>
                            </div>
                            <x-reservation-status :status="$reservation->status->value" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Courts --}}
        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Courts</h2>
                <a href="{{ route('admin.courts.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Manage all</a>
            </div>
            <ul class="divide-y divide-slate-200">
                @forelse ($courts as $court)
                    <li>
                        <a href="{{ route('admin.courts.show', $court) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50">
                            <x-court-photo :court="$court" class="h-11 w-14 shrink-0 rounded-md" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $court->name }}</p>
                                <p class="text-xs text-slate-600">₱{{ number_format((float) $court->hourly_rate, 2) }}/hr</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset
                                         {{ $court->is_active ? 'bg-green-50 text-green-800 ring-green-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                {{ $court->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-600">
                        No courts yet.
                        <a href="{{ route('admin.courts.create') }}" class="font-medium text-slate-900 underline">Add one</a>.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Sidebar: action items --}}
    <div class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Needs your attention</h2>
            </div>

            @if ($onSitePending->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-600">
                    <span class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-green-50">
                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    Nothing pending. All on-site payments are verified.
                </p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($onSitePending as $payment)
                        <li class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $payment->reservation->court->name }}</p>
                                    <p class="truncate text-xs text-slate-600">{{ $payment->reservation->user->name }}</p>
                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $payment->reservation->reference_number }}</p>
                                </div>
                                <p class="shrink-0 text-sm font-semibold text-slate-900">₱{{ number_format((float) $payment->amount, 2) }}</p>
                            </div>
                            <p class="mt-2 text-xs text-amber-700">
                                Held until {{ $payment->reservation->payment_deadline->format('g:i A') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-slate-200 px-5 py-3"><a href="{{ route('admin.payments.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Review payments →</a></div>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Recent activity</h2>
            </div>
            @if ($recentActivity->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-slate-600">No recorded actions yet.</p>
            @else
                <ul class="divide-y divide-slate-200">
                    @foreach ($recentActivity as $log)
                        @php
                            [$entity, $verb] = array_pad(explode('.', $log->action, 2), 2, null);
                            $labels = [
                                'court' => 'Court', 'reservation' => 'Reservation',
                                'payment' => 'Payment', 'auth' => 'Sign-in',
                            ];
                            $headline = trim(($labels[$entity] ?? ucfirst((string) $entity)).' '.($verb ? str_replace('_', ' ', $verb) : ''));
                        @endphp
                        <li class="px-5 py-3">
                            <p class="text-sm text-slate-900">{{ $headline }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at->diffForHumans() }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
