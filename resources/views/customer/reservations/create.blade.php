@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-heading eyebrow="Plan your game" :title="'Reserve '.$court->name" :description="$date->format('l, F j, Y').' · ₱'.number_format($hourlyRate, 2).' per hour'"><a href="{{ route('customer.availability.index', ['court' => $court, 'date' => $date->toDateString()]) }}" class="text-link">← Change court or date</a></x-page-heading>

    @if (count($availableSlots) === 0)
        <div class="border border-slate-700 surface p-8 text-center">
            <p class="text-sm font-medium text-slate-100">No open slots on this date.</p>
            <p class="mt-1 text-sm text-slate-400">Try another date or court.</p>
        </div>
    @else
        @php
            $durations = range($minDuration, $maxDuration, $incrementMinutes);
            $selectedTime = old('time', request('time', \Carbon\CarbonImmutable::parse($availableSlots[0]['start'])->format('H:i')));
        @endphp

        <form method="POST" action="{{ route('customer.reservations.store') }}"
              class="surface space-y-7 p-5 sm:p-8" x-data="bookingForm()">
            @csrf

            <input type="hidden" name="court_id" value="{{ $court->id }}">
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">

            @error('slot')
                <div role="alert" class="rounded-md bg-rose-400/10 px-4 py-3 text-sm text-rose-200">{{ $message }}</div>
            @enderror

            <div>
                <h2 class="booking-step"><span>01</span> Choose your start time</h2>
                <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-label="Start time">
                    @foreach ($availableSlots as $slot)
                        @php $value = \Carbon\CarbonImmutable::parse($slot['start'])->format('H:i'); @endphp
                        <label class="cursor-pointer">
                            <input type="radio" name="time" value="{{ $value }}" x-model="time"
                                   class="peer sr-only" @checked($value === $selectedTime)>
                            <span class="slot-option">
                                {{ \Carbon\CarbonImmutable::parse($slot['start'])->format('g:i A') }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('time')
                    <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <h2 class="booking-step"><span>02</span> How long are you playing?</h2><label for="duration_minutes" class="block text-sm font-medium text-slate-300">Duration</label>
                <select id="duration_minutes" name="duration_minutes" x-model.number="duration"
                        class="mt-1 block w-full rounded-md border border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
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
                <p class="mt-1 text-xs text-slate-400">
                    Between {{ intdiv($minDuration, 60) }} and {{ intdiv($maxDuration, 60) }} hours, in {{ $incrementMinutes }}-minute steps.
                </p>
                @error('duration_minutes')
                    <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <h2 class="booking-step"><span>03</span> Choose how to pay</h2>
                <div class="mt-2 space-y-2" role="radiogroup" aria-label="Payment method">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="online" x-model="method"
                               @checked(old('payment_method', 'online') === 'online') class="mt-0.5">
                        <span>
                            <span class="block text-sm font-medium text-slate-100">Pay online</span>
                            <span class="block text-xs text-slate-400">PayMongo QRPh checkout. Slot held for {{ config('reservation.hold_minutes.online') }} minutes.</span>
                        </span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="on_site" x-model="method"
                               @checked(old('payment_method') === 'on_site') class="mt-0.5">
                        <span>
                            <span class="block text-sm font-medium text-slate-100">Pay on-site</span>
                            <span class="block text-xs text-slate-400">Pay cash at the facility. An administrator verifies your payment.</span>
                        </span>
                    </label>
                </div>
                @error('payment_method')
                    <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl border border-lime-300/20 bg-lime-300/5 p-5">
                <p class="text-sm font-medium text-slate-100">Total</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-lime-300" x-text="new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(total)">₱{{ number_format($hourlyRate * ((int) old('duration_minutes', $minDuration)) / 60, 2) }}</p>
                <p class="mt-1 text-xs text-slate-400">
                    Your total updates with the selected duration. All amounts are in Philippine pesos.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="button button-primary">
                    Reserve this time
                </button>
                <a href="{{ route('customer.availability.index', ['court' => $court, 'date' => $date->toDateString()]) }}"
                   class="text-sm text-slate-400 hover:text-white">Cancel</a>
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
