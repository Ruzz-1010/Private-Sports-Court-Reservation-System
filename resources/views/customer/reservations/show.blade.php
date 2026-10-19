@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('customer.reservations.index') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; My reservations</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $reservation->reference_number }}</h1>
            <x-reservation-status :status="$reservation->status->value" />
        </div>
        <p class="mt-1 text-sm text-slate-600">{{ $reservation->court->name }} · {{ $reservation->court->location }}</p>
    </div>

    @if ($reservation->status->value === 'pending')
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm font-medium text-amber-900">
                Awaiting {{ $reservation->payment_method->value === 'online' ? 'online payment' : 'on-site payment' }}
            </p>
            @if ($reservation->holdIsActive())
                <p class="mt-1 text-sm text-amber-800">
                    This slot is held until
                    <span class="font-semibold">{{ $reservation->payment_deadline->format('M j, Y g:i A') }}</span>.
                    After that it is released automatically.
                </p>
            @else
                <p class="mt-1 text-sm text-amber-800">
                    The payment deadline has passed. This hold will expire and the slot will be released.
                </p>
            @endif
            <div class="mt-4 flex flex-wrap gap-3">
                @if ($reservation->payment_method->value === 'online')
                    <form method="POST" action="{{ route('customer.payments.checkout', $reservation) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Pay online
                        </button>
                    </form>
                @else
                    <p class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700">
                        Pay at the facility, then an administrator verifies it.
                    </p>
                @endif
            </div>
        </div>
    @endif

    @if ($reservation->status->value === 'expired' && $reservation->expiration_reason)
        <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-medium text-slate-900">Expired</p>
            <p class="mt-1 text-sm text-slate-600">{{ $reservation->expiration_reason }}</p>
        </div>
    @endif

    @if ($reservation->status->value === 'cancelled' && $reservation->cancellation_reason)
        <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-medium text-slate-900">Cancelled</p>
            <p class="mt-1 text-sm text-slate-600">{{ $reservation->cancellation_reason }}</p>
        </div>
    @endif

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="font-semibold text-slate-900">Reservation details</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-500">Date</dt>
                <dd class="mt-1 text-slate-900">{{ $reservation->starts_at->format('l, M j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Start time</dt>
                <dd class="mt-1 text-slate-900">{{ $reservation->starts_at->format('g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">End time</dt>
                <dd class="mt-1 text-slate-900">{{ $reservation->ends_at->format('g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Duration</dt>
                <dd class="mt-1 text-slate-900">{{ intdiv($reservation->starts_at->diffInMinutes($reservation->ends_at), 60) }} hours</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Hourly rate (locked)</dt>
                <dd class="mt-1 text-slate-900">₱{{ number_format((float) $reservation->hourly_rate, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Total amount</dt>
                <dd class="mt-1 font-medium text-slate-900">₱{{ number_format((float) $reservation->total_amount, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Payment method</dt>
                <dd class="mt-1 text-slate-900">{{ $reservation->payment_method->value === 'online' ? 'Online' : 'On-site' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Booked on</dt>
                <dd class="mt-1 text-slate-900">{{ $reservation->created_at->format('M j, Y g:i A') }}</dd>
            </div>
        </dl>
    </div>

    @if ($reservation->receipt)
        <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-emerald-900">Payment receipt</h2>
                    <p class="mt-1 text-sm text-emerald-800">
                        Receipt <span class="font-semibold">{{ $reservation->receipt->receipt_number }}</span>
                        &middot; {{ $reservation->receipt->currency }} {{ number_format((float) $reservation->receipt->amount_paid, 2) }} paid
                    </p>
                    <p class="mt-1 text-sm text-emerald-800">You can download this any time from your reservation history.</p>
                </div>
                <a href="{{ route('customer.receipts.show', $reservation->receipt) }}"
                   class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                    Download receipt (PDF)
                </a>
            </div>
        </div>
    @endif

    @can('cancel', $reservation)
        <div class="mt-6 rounded-lg border border-red-200 bg-white p-6">
            <h2 class="font-semibold text-slate-900">Cancel reservation</h2>
            <p class="mt-1 text-sm text-slate-600">This releases the slot immediately and cannot be undone.</p>
            <form method="POST" action="{{ route('customer.reservations.destroy', $reservation) }}" class="mt-3"
                  onsubmit="return confirm('Cancel {{ $reservation->reference_number }}? The slot will be released.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    Cancel reservation
                </button>
            </form>
        </div>
    @endcan
</div>
@endsection
