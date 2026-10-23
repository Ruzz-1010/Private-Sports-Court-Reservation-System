@extends('layouts.app')

@section('content')
<div class="-mx-4 -mt-8 min-w-0 overflow-x-hidden bg-[#050505] text-white sm:-mx-6 lg:-mx-0">
    <section class="px-3 py-6 sm:px-8 sm:py-8 lg:px-14">
        <div class="mx-auto w-full max-w-2xl min-w-0">

            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-[.14em] text-slate-500 transition hover:text-lime-300">
                <span aria-hidden="true">&larr;</span> Home
            </a>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-x-2 gap-y-1.5 sm:mt-4">
                <h1 class="text-sm font-black uppercase leading-5 tracking-tight sm:text-base">Terms and Conditions</h1>
                <p class="rounded-full border border-white/10 px-2.5 py-0.5 text-[9px] uppercase tracking-[.14em] text-slate-500">Updated {{ now()->format('M j, Y') }}</p>
            </div>

            <p class="mt-2 break-words text-[11px] leading-5 text-slate-400">
                Rules for using the {{ config('reservation.facility_name') }} online reservation system.
                By creating an account or booking a court, you agree to them. Tap a heading to read the details.
            </p>

            <div class="mt-4 sm:mt-5">
                @include('public.partials.terms-content')
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-lg border border-lime-300/15 bg-lime-300/[.04] px-3 py-2.5 sm:mt-4 sm:px-3.5 sm:py-3">
                <a href="{{ route('register') }}" class="rounded-md bg-lime-300 px-3.5 py-1.5 text-[10px] font-black uppercase tracking-[.1em] text-slate-950 transition hover:bg-lime-200">Create account</a>
                <a href="{{ route('home') }}" class="text-[11px] font-semibold text-slate-400 transition hover:text-lime-300">Back to home</a>
                <p class="ml-auto hidden text-[9px] uppercase tracking-[.12em] text-slate-600 sm:block">{{ config('reservation.facility_name') }}</p>
            </div>
        </div>
    </section>
</div>
@endsection
