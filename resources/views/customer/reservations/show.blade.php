@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-heading eyebrow="Your booking" :title="$reservation->reference_number" :description="$reservation->court->name.' · '.$reservation->court->location"><x-reservation-status :status="$reservation->status->value" /><a href="{{ route('customer.reservations.index') }}" class="button">← My reservations</a></x-page-heading>

    @if ($reservation->status->value === 'pending')
        <div class="mb-6 rounded-lg border border-amber-400/25 bg-amber-400/10 p-4">
            <p class="text-sm font-medium text-amber-200">
                Awaiting {{ $reservation->payment_method->value === 'online' ? 'online payment' : 'on-site payment' }}
            </p>
            @if ($reservation->holdIsActive())
                <p class="mt-1 text-sm text-amber-300">
                    This slot is held until
                    <span class="font-semibold">{{ $reservation->payment_deadline->format('M j, Y g:i A') }}</span>.
                    After that it is released automatically.
                </p>
            @else
                <p class="mt-1 text-sm text-amber-300">
                    The payment deadline has passed. This hold will expire and the slot will be released.
                </p>
            @endif
            <div class="mt-4 flex flex-wrap gap-3">
                @if ($reservation->payment_method->value === 'online')
                    @if (config('payments.simulation_enabled'))
                        <a href="{{ route('customer.payments.demo', $reservation) }}" class="button button-primary">Open PayMongo test payment</a>
                    @else
                        <form method="POST" action="{{ route('customer.payments.checkout', $reservation) }}">
                            @csrf
                            <button type="submit" class="button button-primary">Pay online</button>
                        </form>
                    @endif
                @else
                    <p class="surface px-4 py-2 text-sm font-medium text-slate-300">
                        Pay at the facility, then an administrator verifies it.
                    </p>
                @endif
            </div>
        </div>
    @endif

    @if ($reservation->status->value === 'expired' && $reservation->expiration_reason)
        <div class="mb-6 border border-slate-700 surface-muted p-4">
            <p class="text-sm font-medium text-slate-100">Expired</p>
            <p class="mt-1 text-sm text-slate-400">{{ $reservation->expiration_reason }}</p>
        </div>
    @endif

    @if ($reservation->status->value === 'cancelled' && $reservation->cancellation_reason)
        <div class="mb-6 border border-slate-700 surface-muted p-4">
            <p class="text-sm font-medium text-slate-100">Cancelled</p>
            <p class="mt-1 text-sm text-slate-400">{{ $reservation->cancellation_reason }}</p>
        </div>
    @endif

    <div class="border border-slate-700 surface p-6">
        <h2 class="font-semibold text-slate-100">Reservation details</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-400">Date</dt>
                <dd class="mt-1 text-slate-100">{{ $reservation->starts_at->format('l, M j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-400">Start time</dt>
                <dd class="mt-1 text-slate-100">{{ $reservation->starts_at->format('g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-400">End time</dt>
                <dd class="mt-1 text-slate-100">{{ $reservation->ends_at->format('g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-400">Duration</dt>
                <dd class="mt-1 text-slate-100">{{ intdiv($reservation->starts_at->diffInMinutes($reservation->ends_at), 60) }} hours</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-400">Hourly rate (locked)</dt>
                <dd class="mt-1 text-slate-100">₱{{ number_format((float) $reservation->hourly_rate, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-400">Total amount</dt>
                <dd class="mt-1 font-medium text-slate-100">₱{{ number_format((float) $reservation->total_amount, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-400">Payment method</dt>
                <dd class="mt-1 text-slate-100">{{ $reservation->payment_method->value === 'online' ? 'Online' : 'On-site' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-400">Booked on</dt>
                <dd class="mt-1 text-slate-100">{{ $reservation->created_at->format('M j, Y g:i A') }}</dd>
            </div>
        </dl>
    </div>

    @if ($reservation->receipt)
        <div class="mt-6 rounded-lg border border-emerald-400/25 bg-emerald-400/10 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-emerald-200">Payment receipt</h2>
                    <p class="mt-1 text-sm text-emerald-300">
                        Receipt <span class="font-semibold">{{ $reservation->receipt->receipt_number }}</span>
                        &middot; {{ $reservation->receipt->currency }} {{ number_format((float) $reservation->receipt->amount_paid, 2) }} paid
                    </p>
                    <p class="mt-1 text-sm text-emerald-300">You can download this any time from your reservation history.</p>
                </div>
                <a href="{{ route('customer.receipts.show', $reservation->receipt) }}"
                   class="button button-primary">
                    Download receipt (PDF)
                </a>
            </div>
        </div>
    @endif

    @can('cancel', $reservation)
        <div class="mt-6 border border-rose-400/25 surface p-6">
            <h2 class="font-semibold text-slate-100">Cancel reservation</h2>
            <p class="mt-1 text-sm text-slate-400">This releases the slot immediately and cannot be undone.</p>
            <form method="POST" action="{{ route('customer.reservations.destroy', $reservation) }}" class="mt-3"
                  onsubmit="return confirm('Cancel {{ $reservation->reference_number }}? The slot will be released.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="button button-danger">
                    Cancel reservation
                </button>
            </form>
        </div>
    @endcan
</div>
@endsection
