@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-slate-900">Welcome, {{ auth()->user()->name }}</h1>
    <p class="mt-1 text-sm text-slate-600">Booking and receipts will appear here.</p>
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">Reservations</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $totalReservations }}</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">Upcoming</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $upcomingCount }}</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">Receipts</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $receiptCount }}</p>
    </div>
</div>

<div class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
    <h2 class="font-semibold text-slate-900">Courts</h2>
    <div class="mt-4 divide-y divide-slate-200">
        @foreach ($courts as $court)
            <div class="flex items-center justify-between py-3">
                <div>
                    <p class="font-medium text-slate-900">{{ $court->name }}</p>
                    <p class="text-sm text-slate-600">{{ $court->location }}</p>
                </div>
                <p class="text-sm font-medium text-slate-900">
                    ₱{{ number_format((float) $court->hourly_rate, 2) }} / hour
                </p>
            </div>
        @endforeach
    </div>
</div>
@endsection
