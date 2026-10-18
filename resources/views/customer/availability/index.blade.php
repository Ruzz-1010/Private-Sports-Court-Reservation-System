@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Availability calendar</h1>
    <p class="mt-1 text-sm text-slate-600">Pick a court and date to see which slots are open.</p>
</div>

<form method="GET" class="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
    <div>
        <label for="court_id" class="block text-sm font-medium text-slate-700">Court</label>
        <select id="court_id" name="court_id" onchange="this.form.submit()"
                class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
            @foreach ($courts as $option)
                <option value="{{ $option->id }}" @selected($option->id === $court->id)>
                    {{ $option->name }} — ₱{{ number_format((float) $option->hourly_rate, 2) }}/hr
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="date" class="block text-sm font-medium text-slate-700">Date</label>
        <input id="date" name="date" type="date" value="{{ $date->toDateString() }}"
               min="{{ now()->toDateString() }}"
               max="{{ now()->addDays(config('reservation.advance_booking_days'))->toDateString() }}"
               onchange="this.form.submit()"
               class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
    </div>
    <noscript><button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white">Update</button></noscript>
</form>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2">
        <a href="{{ route('customer.availability.index', ['court' => $court, 'date' => $previousDate->toDateString()]) }}"
           class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">&larr; Previous</a>
        <p class="font-medium text-slate-900">{{ $dateLabel }}</p>
        <a href="{{ route('customer.availability.index', ['court' => $court, 'date' => $nextDate->toDateString()]) }}"
           class="rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Next &rarr;</a>
    </div>

    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600">
        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-green-100 ring-1 ring-green-300"></span>Available</span>
        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-amber-100 ring-1 ring-amber-300"></span>Pending hold</span>
        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-slate-200 ring-1 ring-slate-400"></span>Reserved</span>
        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-red-100 ring-1 ring-red-300"></span>Blocked</span>
    </div>
</div>

@if (! $isOpenDay)
    <div class="rounded-lg border border-slate-200 bg-white p-8 text-center">
        <p class="text-sm font-medium text-slate-900">The facility is closed on this day.</p>
        <p class="mt-1 text-sm text-slate-600">Please choose another date.</p>
    </div>
@else
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="w-full min-w-[40rem] divide-y divide-slate-200">
            <caption class="sr-only">Availability for {{ $court->name }} on {{ $dateLabel }}</caption>
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Time</th>
                    <th scope="col" class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                    <th scope="col" class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach ($slots as $slot)
                    @php
                        $time = \Carbon\CarbonImmutable::parse($slot['start'])->format('g:i A');
                        $canBook = $slot['status'] === \App\Services\Availability\AvailabilityService::AVAILABLE;
                        $cell = match ($slot['status']) {
                            'available' => 'bg-green-50/40',
                            'pending'   => 'bg-amber-50/60',
                            'reserved'  => 'bg-slate-100/60',
                            'blocked'   => 'bg-red-50/60',
                            default     => '',
                        };
                    @endphp
                    <tr class="{{ $cell }}">
                        <td class="px-4 py-2 text-sm font-medium text-slate-900">{{ $time }}</td>
                        <td class="px-4 py-2 text-sm text-slate-700">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium
                                @switch($slot['status'])
                                    @case('available') class="bg-green-100 text-green-800" @break
                                    @case('pending') class="bg-amber-100 text-amber-800" @break
                                    @case('reserved') class="bg-slate-200 text-slate-800" @break
                                    @case('blocked') class="bg-red-100 text-red-800" @break
                                    @default class="bg-slate-100 text-slate-600" @break
                                @endswitch">{{ ucfirst($slot['status']) }}</span>
                            @if ($slot['reason'])
                                <span class="ml-2 text-xs text-slate-600">{{ $slot['reason'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if ($canBook)
                                <a href="{{ route('customer.reservations.create', ['court' => $court, 'date' => $date->toDateString(), 'time' => \Carbon\CarbonImmutable::parse($slot['start'])->format('H:i')]) }}"
                                   class="text-sm font-medium text-slate-900 underline hover:text-slate-700">Book</a>
                            @else
                                <span class="text-xs text-slate-400">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-xs text-slate-500">
        {{ $slotCount }} slots of {{ $incrementMinutes }} minutes.
        Bookings run {{ config('reservation.operating_hours.start') }}:00 to {{ config('reservation.operating_hours.end') }}:00.
    </p>
@endif
@endsection
