@extends('layouts.admin')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">On-site payment verification</h1>
    <p class="mt-1 text-sm text-slate-600">
        Cash payments held at the facility. Verify once the money is received.
    </p>
</div>

@if ($payments->isEmpty())
    <div class="rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </span>
        <p class="mt-3 font-medium text-slate-900">Nothing to verify</p>
        <p class="mt-1 text-sm text-slate-600">All on-site payments have been settled.</p>
    </div>
@else
    <div class="space-y-4">
        @foreach ($payments as $payment)
            @php $reservation = $payment->reservation; @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-slate-900">{{ $reservation->court?->name }}</h2>
                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                                Awaiting verification
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-slate-600">
                            {{ $reservation->user?->name }} &middot; {{ $reservation->user?->email }}
                        </p>
                        <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $reservation->reference_number }}</p>

                        <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                            <div class="flex gap-2">
                                <dt class="text-slate-500">Schedule:</dt>
                                <dd class="font-medium text-slate-900">
                                    {{ $reservation->starts_at->format('M j, Y g:i A') }} &ndash; {{ $reservation->ends_at->format('g:i A') }}
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="text-slate-500">Held until:</dt>
                                <dd class="font-medium text-amber-700">{{ $reservation->payment_deadline->format('g:i A') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="text-right">
                        <p class="text-sm text-slate-500">Amount due</p>
                        <p class="text-2xl font-bold text-slate-900">₱{{ number_format((float) $payment->amount, 2) }}</p>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4">
                    <form method="POST" action="{{ route('admin.payments.verify', $payment) }}"
                          onsubmit="return confirm('Verify ₱{{ number_format((float) $payment->amount, 2) }} received for {{ $reservation->reference_number }}?');">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Verify payment received
                        </button>
                    </form>

                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            onclick="document.getElementById('reject-{{ $payment->id }}').classList.toggle('hidden')">
                        Reject
                    </button>

                    <a href="{{ route('customer.reservations.show', $reservation) }}"
                       class="text-sm text-slate-600 underline hover:text-slate-900">View reservation</a>
                </div>

                <form id="reject-{{ $payment->id }}" method="POST" action="{{ route('admin.payments.reject', $payment) }}"
                      class="mt-4 hidden rounded-lg bg-slate-50 p-4">
                    @csrf
                    <label for="reason-{{ $payment->id }}" class="block text-sm font-medium text-slate-700">
                        Reason for rejection
                    </label>
                    <input id="reason-{{ $payment->id }}" name="reason" type="text" required
                           placeholder="e.g. Customer did not pay at the facility"
                           class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
                    @error('reason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="mt-3 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                        Confirm rejection
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    @if ($payments->hasPages())
        <div class="mt-6">{{ $payments->links() }}</div>
    @endif
@endif
@endsection
