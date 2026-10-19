@extends('layouts.app')

@section('content')
@php($courts = \App\Models\Court::query()->active()->orderBy('name')->get())

<section class="motion-reveal relative isolate overflow-hidden rounded-[2rem] bg-slate-950 px-6 py-16 text-white shadow-2xl shadow-slate-300 sm:px-10 lg:px-16 lg:py-24">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,_rgba(20,184,166,0.38),transparent_30%),radial-gradient(circle_at_15%_85%,_rgba(79,70,229,0.45),transparent_34%)]"></div>
    <div class="absolute -right-20 top-16 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
    <div class="grid items-center gap-12 lg:grid-cols-[1.15fr_.85fr]">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-teal-300/30 bg-teal-300/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.16em] text-teal-100"><span class="h-1.5 w-1.5 rounded-full bg-teal-300"></span> Barangay San Jose · Puerto Princesa</div>
            <h1 class="mt-6 max-w-3xl text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">Your game starts with <span class="text-teal-300">one simple booking.</span></h1>
            <p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">Find an open court, reserve your time, and get a confirmed booking without the back-and-forth.</p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl bg-teal-300 px-5 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-teal-400/20 transition hover:-translate-y-0.5 hover:bg-teal-200">Open my dashboard <span class="ml-2">→</span></a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-teal-300 px-5 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-teal-400/20 transition hover:-translate-y-0.5 hover:bg-teal-200">Reserve a court <span class="ml-2">→</span></a>
                    <a href="#courts" class="inline-flex items-center justify-center rounded-xl border border-white/15 bg-white/5 px-5 py-3.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/10">Explore courts</a>
                @endauth
            </div>
            <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm text-slate-300"><span class="inline-flex items-center gap-2"><span class="text-teal-300">✓</span> Live availability</span><span class="inline-flex items-center gap-2"><span class="text-teal-300">✓</span> Secure payment flow</span><span class="inline-flex items-center gap-2"><span class="text-teal-300">✓</span> Instant confirmation</span></div>
        </div>
        <div class="rounded-3xl border border-white/15 bg-white/10 p-5 shadow-xl backdrop-blur-sm sm:p-6">
            <div class="flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-200">Fast booking</p><h2 class="mt-1 text-2xl font-bold">Ready when you are.</h2></div><div class="grid h-12 w-12 place-items-center rounded-2xl bg-teal-300 text-xl text-slate-950">⌁</div></div>
            <ol class="mt-7 space-y-4">
                <li class="flex items-center gap-4 rounded-2xl bg-slate-950/35 p-4"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal-300 text-sm font-black text-slate-950">1</span><div><p class="font-bold">Choose your court</p><p class="mt-0.5 text-sm text-slate-300">See the schedule before you reserve.</p></div></li>
                <li class="flex items-center gap-4 rounded-2xl bg-slate-950/35 p-4"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal-300 text-sm font-black text-slate-950">2</span><div><p class="font-bold">Pick a time</p><p class="mt-0.5 text-sm text-slate-300">Reserve in flexible 30-minute slots.</p></div></li>
                <li class="flex items-center gap-4 rounded-2xl bg-slate-950/35 p-4"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-teal-300 text-sm font-black text-slate-950">3</span><div><p class="font-bold">Complete payment</p><p class="mt-0.5 text-sm text-slate-300">Your confirmation follows verified payment.</p></div></li>
            </ol>
        </div>
    </div>
</section>

<section id="courts" class="py-16 sm:py-20">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-bold uppercase tracking-[0.16em] text-indigo-600">Play your way</p><h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Courts made for game day.</h2></div><p class="max-w-md text-sm leading-6 text-slate-600">Choose a facility, check the open schedule, and lock in your next match.</p></div>
    <div class="motion-reveal-delay mt-9 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($courts as $court)
            <article class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/70"><div class="relative"><x-court-photo :court="$court" class="h-52 rounded-none border-0 transition duration-500 group-hover:scale-105" /><div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-950/55 to-transparent"></div><span class="absolute bottom-4 left-4 rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-slate-950 backdrop-blur">₱{{ number_format((float) $court->hourly_rate, 2) }}/hour</span></div><div class="p-6"><h3 class="text-xl font-black text-slate-950">{{ $court->name }}</h3><p class="mt-2 text-sm text-slate-600">{{ $court->location }}</p><a href="{{ auth()->check() ? route('customer.availability.index', $court) : route('register') }}" class="mt-5 inline-flex items-center text-sm font-bold text-indigo-600 hover:text-indigo-800">Check availability <span class="ml-2">→</span></a></div></article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-600 md:col-span-2 lg:col-span-3">Courts will appear here once the administrator adds them.</div>
        @endforelse
    </div>
</section>

<section class="mb-8 grid gap-5 rounded-3xl bg-indigo-50 p-7 sm:grid-cols-3 sm:p-9"><div><p class="text-sm font-bold uppercase tracking-[0.15em] text-indigo-600">Why book here</p><h2 class="mt-2 text-2xl font-black text-slate-950">Built around your time.</h2></div><div class="rounded-2xl bg-white p-5"><p class="text-lg font-black text-slate-950">Clear schedules</p><p class="mt-2 text-sm leading-6 text-slate-600">Know which slots are open before you start a reservation.</p></div><div class="rounded-2xl bg-white p-5"><p class="text-lg font-black text-slate-950">Booking you can trust</p><p class="mt-2 text-sm leading-6 text-slate-600">Conflicting reservations are blocked before your booking is confirmed.</p></div></section>
@endsection
