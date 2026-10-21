@extends('layouts.app')

@section('content')
<x-page-heading eyebrow="Make time for play" title="Find your next game" description="Choose a court and date, then pick an available time to reserve." />
<form method="GET" class="filter-panel">
    <div class="flex-1">
        <label for="court_id" class="block text-xs font-semibold text-slate-300">Court</label>
        <select id="court_id" name="court_id" class="mt-2 w-full">
            @foreach ($courts as $option)
                <option value="{{ $option->id }}" @selected($option->id === $court->id)>{{ $option->name }} — ₱{{ number_format((float) $option->hourly_rate, 2) }}/hr</option>
            @endforeach
        </select>
    </div>
    <div class="flex-1">
        <label for="date" class="block text-xs font-semibold text-slate-300">Play date</label>
        <input id="date" name="date" type="date" value="{{ $date->toDateString() }}"
               min="{{ now(config('reservation.timezone'))->toDateString() }}"
               max="{{ now(config('reservation.timezone'))->addDays(config('reservation.advance_booking_days'))->toDateString() }}" class="mt-2 w-full">
    </div>
    <button type="submit" class="button button-primary">Find available times <span aria-hidden="true">→</span></button>
</form>

<section class="surface mb-6 overflow-hidden">
    <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:p-6">
        <x-court-photo :court="$court" class="h-28 w-full shrink-0 rounded-xl sm:h-24 sm:w-36" />
        <div class="min-w-0 flex-1">
            <p class="workspace-kicker">YOUR SELECTED COURT</p>
            <h2 class="mt-2 text-xl font-semibold text-slate-100">{{ $court->name }}</h2>
            <p class="mt-1 text-sm text-slate-400">{{ $court->location }}</p>
        </div>
        <div class="sm:text-right"><p class="text-2xl font-bold tracking-tight text-lime-300">₱{{ number_format((float) $court->hourly_rate, 2) }}<span class="text-xs font-normal text-slate-400"> / hour</span></p><p class="mt-1 text-xs text-slate-400">All times in {{ config('reservation.timezone') }}</p></div>
    </div>
</section>

<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div><h2 class="text-lg font-semibold text-slate-100">{{ $dateLabel }}</h2><p class="mt-1 text-xs text-slate-400">Choose a start time to continue to your booking.</p></div>
    <div class="flex gap-2">
        <a href="{{ route('customer.availability.index', ['court' => $court, 'date' => $previousDate->toDateString()]) }}" class="button" aria-label="Previous date">← Previous</a>
        <a href="{{ route('customer.availability.index', ['court' => $court, 'date' => $nextDate->toDateString()]) }}" class="button" aria-label="Next date">Next →</a>
    </div>
</div>

@if (! $isOpenDay)
    <div class="empty-state"><p class="font-semibold text-slate-100">A rest day for the courts</p><p class="text-sm text-slate-400">The facility is closed on this date. Choose another day to play.</p></div>
@else
    <div class="mb-5 flex flex-wrap gap-2" aria-label="Slot status legend"><span class="badge badge-success">Available</span><span class="badge badge-warning">Pending</span><span class="badge">Reserved</span><span class="badge badge-danger">Blocked</span></div>
    <div class="availability-grid" aria-label="Availability for {{ $court->name }} on {{ $dateLabel }}">
        @foreach ($slots as $slot)
            @php
                $slotTime = \Carbon\CarbonImmutable::parse($slot['start'])->format('g:i A');
                $canBook = $slot['status'] === \App\Services\Availability\AvailabilityService::AVAILABLE;
                $slotTone = match ($slot['status']) {
                    'available' => 'badge-success',
                    'pending' => 'badge-warning',
                    'blocked' => 'badge-danger',
                    default => '',
                };
            @endphp
            <article class="availability-slot {{ $canBook ? 'is-available' : '' }}">
                <p class="slot-time">{{ $slotTime }}</p>
                <span class="badge {{ $slotTone }}">{{ ucfirst($slot['status']) }}</span>
                @if ($slot['reason'])<p class="slot-reason">{{ $slot['reason'] }}</p>@endif
                @if ($canBook)
                    <a href="{{ route('customer.reservations.create', ['court' => $court, 'date' => $date->toDateString(), 'time' => \Carbon\CarbonImmutable::parse($slot['start'])->format('H:i')]) }}" class="button button-primary" aria-label="Book {{ $court->name }} at {{ $slotTime }}">Book time <span aria-hidden="true">↗</span></a>
                @else
                    <p class="mt-auto text-xs text-slate-400">Unavailable for booking</p>
                @endif
            </article>
        @endforeach
    </div>
    <p class="mt-5 text-xs leading-6 text-slate-400">{{ $slotCount }} slots · {{ $incrementMinutes }}-minute increments · Facility hours: {{ config('reservation.operating_hours.start') }}:00–{{ config('reservation.operating_hours.end') }}:00. Select a time to choose your duration.</p>
@endif
@endsection
