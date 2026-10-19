@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('customer.availability.index', ['court' => $court, 'date' => $date->toDateString()]) }}"
           class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to availability</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">Reserve {{ $court->name }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $date->format('l, F j, Y') }} · ₱{{ number_format($hourlyRate, 2) }} per hour</p>
    </div>

    @if (count($availableSlots) === 0)
        <div class="rounded-lg border border-slate-200 bg-white p-8 text-center">
            <p class="text-sm font-medium text-slate-900">No open slots on this date.</p>
            <p class="mt-1 text-sm text-slate-600">Try another date or court.</p>
        </div>
    @else
        @php
            $durations = range($minDuration, $maxDuration, $incrementMinutes);
            $selectedTime = old('time', request('time', \Carbon\CarbonImmutable::parse($availableSlots[0]['start'])->format('H:i')));
        @endphp

        <form method="POST" action="{{ route('customer.reservations.store') }}"
              class="space-y-6 rounded-lg bg-white p-6 shadow-sm" x-data="bookingForm()">
            @csrf

            <input type="hidden" name="court_id" value="{{ $court->id }}">
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">

            @error('slot')
                <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
            @enderror

            <div>
                <span class="block text-sm font-medium text-slate-700">Start time</span>
                <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-label="Start time">
                    @foreach ($availableSlots as $slot)
                        @php $value = \Carbon\CarbonImmutable::parse($slot['start'])->format('H:i'); @endphp
                        <label class="cursor-pointer">
                            <input type="radio" name="time" value="{{ $value }}" x-model="time"
                                   class="peer sr-only" @checked($value === $selectedTime)>
                            <span class="inline-flex rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700
                                         peer-checked:border-slate-900 peer-checked:bg-slate-900 peer-checked:text-white
                                         peer-focus-visible:ring-2 peer-focus-visible:ring-slate-500">
                                {{ \Carbon\CarbonImmutable::parse($slot['start'])->format('g:i A') }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('time')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="duration_minutes" class="block text-sm font-medium text-slate-700">Duration</label>
                <select id="duration_minutes" name="duration_minutes" x-model.number="duration"
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
                    @foreach ($durations as $minutes)
                        @php
                            $hours = intdiv($minutes, 60);
                            $remainder = $minutes % 60;
                            $label = $remainder === 0
                                ? $hours.' '.\Illuminate\Support\Str::plural('hour', $hours)
                                : $hours.' hr '.$remainder.' min';
                        @endphp
                        <option value="{{ $minutes }}" @selected($minutes === $minDuration)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">
                    Between {{ intdiv($minDuration, 60) }} and {{ intdiv($maxDuration, 60) }} hours, in {{ $incrementMinutes }}-minute steps.
                </p>
                @error('duration_minutes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <span class="block text-sm font-medium text-slate-700">Payment method</span>
                <div class="mt-2 space-y-2" role="radiogroup" aria-label="Payment method">
                    <label class="flex cursor-pointer items-start gap-3 rounded-md border border-slate-300 p-3 has-[:checked]:border-slate-900">
                        <input type="radio" name="payment_method" value="online" x-model="method"
                               @checked(old('payment_method', 'online') === 'online') class="mt-0.5">
                        <span>
                            <span class="block text-sm font-medium text-slate-900">Pay online</span>
                            <span class="block text-xs text-slate-600">PayMongo QRPh checkout. Slot held for {{ config('reservation.hold_minutes.online') }} minutes.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-md border border-slate-300 p-3 has-[:checked]:border-slate-900">
                        <input type="radio" name="payment_method" value="on_site" x-model="method"
                               @checked(old('payment_method') === 'on_site') class="mt-0.5">
                        <span>
                            <span class="block text-sm font-medium text-slate-900">Pay on-site</span>
                            <span class="block text-xs text-slate-600">Pay cash at the facility. An administrator verifies your payment.</span>
                        </span>
                    </label>
                </div>
                @error('payment_method')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-md bg-slate-50 p-4">
                <p class="text-sm font-medium text-slate-900">Total</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900" x-text="total.toFixed(2)"></p>
                <p class="mt-1 text-xs text-slate-600">
                    x-data sets this from the selected duration. The server recalculates it before saving.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Hold this slot
                </button>
                <a href="{{ route('customer.availability.index', ['court' => $court, 'date' => $date->toDateString()]) }}"
                   class="text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            </div>
        </form>

        @push('scripts')
        <script>
            function bookingForm() {
                return {
                    time: @js(old('time', $selectedTime)),
                    duration: @js((int) old('duration_minutes', $minDuration)),
                    method: @js(old('payment_method', 'online')),
                    rate: @js($hourlyRate),
                    get total() { return (this.rate * this.duration) / 60; },
                };
            }
        </script>
        @endpush
    @endif
</div>
@endsection
