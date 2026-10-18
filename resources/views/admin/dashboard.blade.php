@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-slate-900">Administrator dashboard</h1>
    <p class="mt-1 text-sm text-slate-600">Signed in as {{ auth()->user()->name }}</p>
</div>

<div class="grid gap-4 sm:grid-cols-4">
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">Courts</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $totalCourts }}</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">Pending</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $pendingCount }}</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">Confirmed</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $confirmedCount }}</p>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">Collected</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">₱{{ number_format((float) $collectedTotal, 2) }}</p>
    </div>
</div>

<div class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
    <h2 class="font-semibold text-slate-900">Court management</h2>
    <div class="mt-4 divide-y divide-slate-200">
        @foreach ($courts as $court)
            <div class="flex items-center gap-4 py-3">
                <x-court-photo :court="$court" class="h-12 w-16 shrink-0 rounded-md" />
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-slate-900">{{ $court->name }}</p>
                    <p class="text-sm text-slate-600">
                        {{ $court->location }} · ₱{{ number_format((float) $court->hourly_rate, 2) }}/hr
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                             {{ $court->is_active ? 'bg-green-50 text-green-800' : 'bg-slate-100 text-slate-700' }}">
                    {{ $court->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        @endforeach
    </div>
</div>
@endsection
