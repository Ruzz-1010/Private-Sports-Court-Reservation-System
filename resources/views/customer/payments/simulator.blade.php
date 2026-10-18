@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl">

    @if (! $isFakeGateway)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            The simulator is only available when <code class="font-mono">PAYMENT_GATEWAY=fake</code>.
            With the live gateway the customer is redirected to PayMongo instead.
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('customer.reservations.show', $reservation) }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to reservation</a>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">Payment simulator</h1>
        <p class="mt-1 text-sm text-slate-600">
            Stands in for PayMongo's hosted checkout page. Each button sends a signed webhook to
            this application's real webhook endpoint.
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-500">Court</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ $reservation->court->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Amount</dt>
                <dd class="mt-1 font-semibold text-slate-900">₱{{ number_format((float) $payment->amount, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Reference</dt>
                <dd class="mt-1 font-mono text-sm text-slate-900">{{ $reservation->reference_number }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Status</dt>
                <dd class="mt-1"><x-reservation-status :status="$reservation->status->value" /></dd>
            </div>
        </dl>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-semibold text-slate-900">Send a webhook outcome</h2>
        <p class="mt-1 text-sm text-slate-600">
            These exercise the exact code path a real provider callback uses.
        </p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @php
                $outcomes = [
                    'paid' => ['Payment succeeds', 'Signs and confirms the reservation, issues a receipt, queues the email.', 'bg-slate-900'],
                    'failed' => ['Payment fails', 'Valid signature but a non-paid status. Reservation stays pending.', 'border border-slate-300 text-slate-700'],
                    'amount_mismatch' => ['Amount mismatch', 'Signature is valid but the amount is wrong. Held for review.', 'border border-amber-300 text-amber-800'],
                ];
            @endphp

            @foreach ($outcomes as $value => [$label, $help, $classes])
                <form method="POST" action="{{ route('customer.payments.simulator.send', $reservation->reference_number) }}">
                    @csrf
                    <input type="hidden" name="outcome" value="{{ $value }}">
                    <button type="submit" class="w-full rounded-lg px-4 py-3 text-left text-sm font-semibold transition {{ $classes }} hover:opacity-90">
                        {{ $label }}
                    </button>
                    <p class="mt-1 px-1 text-xs text-slate-500">{{ $help }}</p>
                </form>
            @endforeach

            <form method="POST" action="{{ route('customer.payments.simulator.send', $reservation->reference_number) }}">
                @csrf
                <input type="hidden" name="outcome" value="forged">
                <button type="submit" class="w-full rounded-lg border border-red-300 px-4 py-3 text-left text-sm font-semibold text-red-800 hover:bg-red-50">
                    Spoofed signature
                </button>
                <p class="mt-1 px-1 text-xs text-slate-500">Sends an invalid signature. Must be rejected with 401.</p>
            </form>
        </div>
    </div>

    <div class="mt-6 rounded-lg bg-slate-900 p-5 text-slate-300">
        <p class="text-xs leading-relaxed">
            <span class="font-semibold text-white">How this works:</span> the simulator builds the same JSON
            shape PayMongo sends, signs it with HMAC-SHA256, and POSTs it to
            <code class="font-mono text-slate-100">/{{ config('payments.webhook.path') }}</code>.
            The webhook controller then runs signature verification, idempotency, amount and currency
            validation, and the confirmation transaction &mdash; all unchanged production code.
        </p>
    </div>
</div>
@endsection
