@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl px-6 py-12">
        <div class="surface rounded-3xl border border-slate-700 p-8 text-center shadow-xl">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-lime-300">PayMongo sandbox demo</p>
            <h1 class="mt-3 text-3xl font-black text-white">Scan to pay</h1>
            <p class="mx-auto mt-3 max-w-md text-sm text-slate-400">This QR is for presentation only. No real money will be deducted.</p>
            <div class="mx-auto mt-8 grid h-64 w-64 place-items-center rounded-2xl bg-white p-5 shadow-2xl">
                <div class="grid h-full w-full grid-cols-9 gap-1 bg-white" aria-label="Demo QR code">
                    @for ($i = 0; $i < 81; $i++)
                        <span class="rounded-sm {{ (($i * 17 + $reservation->id * 7) % 5 < 2 || in_array($i, [0,1,2,9,10,18,6,7,8,15,16,17,72,73,74,63,71,80])) ? 'bg-slate-950' : 'bg-white' }}"></span>
                    @endfor
                </div>
            </div>
            <div class="mt-7 rounded-2xl bg-slate-900/70 p-4 text-left text-sm text-slate-300">
                <div class="flex justify-between"><span>Reservation</span><strong class="text-white">{{ $reservation->reference_number }}</strong></div>
                <div class="mt-2 flex justify-between"><span>Amount</span><strong class="text-lime-300">₱{{ number_format((float) $payment->amount, 2) }}</strong></div>
            </div>
            <form class="mt-6" method="POST" action="{{ route('customer.payments.demo.simulate', $reservation) }}">
                @csrf
                <button class="w-full rounded-xl bg-lime-300 px-5 py-3 font-black text-slate-950 transition hover:bg-lime-200" type="submit">Simulate Payment</button>
            </form>
            <p class="mt-3 text-xs text-slate-500">Demo mode only · PayMongo QRPh integration remains available when enabled.</p>
        </div>
    </div>
@endsection
