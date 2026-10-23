@extends('layouts.admin')

@section('content')
<x-page-heading eyebrow="Reservation record" :title="$reservation->reference_number" description="Booking details, payment activity, and your issued receipt."><x-reservation-status :status="$reservation->status->value" /><a href="{{ route('admin.reservations.index') }}" class="button">← All reservations</a></x-page-heading>
<div class="grid gap-6 lg:grid-cols-3">
    <section class="border border-slate-700 surface p-6 shadow-sm lg:col-span-2"><h2 class="text-lg font-bold text-slate-100">Booking details</h2><dl class="mt-5 grid gap-5 sm:grid-cols-2"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Court</dt><dd class="mt-1 font-semibold text-slate-100">{{ $reservation->court?->name }}</dd><dd class="text-sm text-slate-400">{{ $reservation->court?->location }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Customer</dt><dd class="mt-1 font-semibold text-slate-100">{{ $reservation->user?->name }}</dd><dd class="text-sm text-slate-400">{{ $reservation->user?->email }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Schedule</dt><dd class="mt-1 font-semibold text-slate-100">{{ $reservation->starts_at->setTimezone(config('reservation.timezone'))->format('l, M j, Y') }}</dd><dd class="text-sm text-slate-400">{{ $reservation->starts_at->setTimezone(config('reservation.timezone'))->format('g:i A') }}–{{ $reservation->ends_at->setTimezone(config('reservation.timezone'))->format('g:i A') }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Quoted total</dt><dd class="mt-1 text-xl font-bold text-slate-100">₱{{ number_format((float) $reservation->total_amount, 2) }}</dd><dd class="text-sm text-slate-400">{{ ucfirst(str_replace('_', ' ', $reservation->payment_method->value)) }}</dd></div></dl></section>
    <aside class="button button-primary p-6"><p class="text-sm font-medium text-slate-400">Payment deadline</p><p class="mt-2 text-lg font-bold">{{ $reservation->payment_deadline?->setTimezone(config('reservation.timezone'))->format('M j, g:i A') ?? 'Not applicable' }}</p><p class="mt-4 text-sm text-slate-400">A reservation is confirmed only after a verified payment.</p></aside>
</div>
@if ($reservation->receipt)
    <section class="mt-6 rounded-2xl border border-emerald-400/25 bg-emerald-400/10 p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-emerald-200">Receipt issued</h2>
                <p class="mt-1 text-sm text-emerald-300">
                    {{ $reservation->receipt->receipt_number }} &middot;
                    {{ $reservation->receipt->currency }} {{ number_format((float) $reservation->receipt->amount_paid, 2) }} paid
                    on {{ $reservation->receipt->transaction_date->setTimezone(config('reservation.timezone'))->format('M j, Y g:i A') }}
                </p>
            </div>
            <a href="{{ route('customer.receipts.show', $reservation->receipt) }}"
               class="button button-primary">
                Download receipt (PDF)
            </a>
        </div>

        <div class="mt-4 rounded-xl border border-white/10 bg-black/20 p-4">
            @php
                $paid = (float) $reservation->receipt->amount_paid;
                $feePercent = (float) config('reservation.refund.admin_fee_percent');
                $fee = round($paid * $feePercent / 100, 2);
                $back = round($paid - $fee, 2);
            @endphp
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">If this customer asks for a refund</p>
            <dl class="mt-2 grid gap-3 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-xs text-slate-400">Amount paid</dt>
                    <dd class="font-semibold text-slate-100">&#8369;{{ number_format($paid, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-400">Administration fee ({{ rtrim(rtrim(number_format($feePercent, 2), '0'), '.') }}%)</dt>
                    <dd class="font-semibold text-amber-300">&#8369;{{ number_format($fee, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-400">Return to customer</dt>
                    <dd class="font-semibold text-emerald-300">&#8369;{{ number_format($back, 2) }}</dd>
                </div>
            </dl>
            <p class="mt-2 text-xs text-slate-400">
                Requests are accepted until {{ config('reservation.refund.request_deadline_hours') }} hours before the slot.
                Refunds are released outside the system, then the payment is marked refunded.
            </p>
        </div>

        <div class="mt-4 border-t border-emerald-400/25 pt-4">
            <p class="text-sm text-emerald-300">
                The same receipt and confirmation was emailed to
                <span class="font-semibold">{{ $reservation->user?->email }}</span>.
                Use this button if the customer did not receive it.
            </p>
            <form method="POST" action="{{ route('admin.reservations.resend-confirmation', $reservation) }}" class="mt-3"
                  onsubmit="return confirm('Send the confirmation email and receipt again to {{ $reservation->user?->email }}?');">
                @csrf
                <button type="submit" class="rounded-lg border border-emerald-400/40 px-4 py-2 text-sm font-semibold text-emerald-200 hover:bg-emerald-400/10">
                    Resend confirmation email
                </button>
            </form>
            @error('email')
                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
            @enderror
        </div>
    </section>
@endif

<section class="mt-6 overflow-hidden border border-slate-700 surface shadow-sm"><div class="border-b border-slate-700/60 px-6 py-4"><h2 class="font-bold text-slate-100">Payment attempts</h2></div><div class="divide-y divide-slate-700/60">@forelse($reservation->payments as $payment)<div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4"><div><p class="font-semibold text-slate-100">₱{{ number_format((float) $payment->amount, 2) }} · {{ ucfirst(str_replace('_', ' ', $payment->method->value)) }}</p><p class="mt-1 text-xs text-slate-400">{{ $payment->created_at->setTimezone(config('reservation.timezone'))->format('M j, Y g:i A') }}</p></div><span class="rounded-full surface-raised px-3 py-1 text-xs font-semibold text-slate-300">{{ ucfirst(str_replace('_', ' ', $payment->status->value)) }}</span></div>@empty<div class="px-6 py-10 text-center text-sm text-slate-400">No payment attempts recorded.</div>@endforelse</div></section>
@endsection
