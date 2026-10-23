@extends('layouts.app')
@section('content')
<div x-data="{ termsOpen: false }" class="auth-shell mx-auto max-w-md rounded-2xl border border-white/10 bg-[#111827] p-6 shadow-2xl shadow-black/40 sm:p-8"><div class="flex items-center justify-between"><a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 transition hover:text-lime-300"><span aria-hidden="true">←</span> Home</a><img src="{{ asset('images/san-jose-pickleball-logo.png') }}" alt="San Jose Pickleball" class="h-12 w-12 object-contain"></div><div class="mt-8"><p class="text-xs font-bold uppercase tracking-[.18em] text-lime-300">Join the club</p><h1 class="mt-2 text-2xl font-bold tracking-tight text-white">Create account</h1><p class="mt-2 text-sm text-slate-400">Use your email for confirmations and receipts.</p></div><form method="POST" action="{{ route('register') }}" class="mt-7 space-y-4">@csrf<div><label for="name" class="block text-sm font-semibold text-slate-300">Full name</label><input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-900 px-3.5 py-3 text-sm text-white outline-none transition focus:border-lime-300 focus:ring-2 focus:ring-lime-300/20">@error('name')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror</div><div><label for="email" class="block text-sm font-semibold text-slate-300">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-900 px-3.5 py-3 text-sm text-white outline-none transition focus:border-lime-300 focus:ring-2 focus:ring-lime-300/20">@error('email')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror</div><div class="grid gap-4 sm:grid-cols-2">@foreach([['password','Password'],['password_confirmation','Confirm password']] as [$id,$label])<div><label for="{{ $id }}" class="block text-sm font-semibold text-slate-300">{{ $label }}</label><div x-data="{ show: false }" class="relative"><input id="{{ $id }}" x-bind:type="show ? 'text' : 'password'" name="{{ $id }}" required autocomplete="new-password" class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-900 px-3.5 py-3 pr-10 text-sm text-white outline-none transition focus:border-lime-300 focus:ring-2 focus:ring-lime-300/20"><button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-lime-300" :aria-label="show ? 'Hide password' : 'Show password'"><span x-text="show ? '◉' : '◌'" class="text-lg"></span></button></div></div>@endforeach</div>@error('password')<p class="text-sm text-red-300">{{ $message }}</p>@enderror<button type="submit" class="w-full rounded-lg bg-lime-300 px-4 py-3 text-sm font-bold text-slate-950 transition hover:bg-lime-200">Create account</button><label class="flex items-start gap-3 text-sm text-slate-400"><input type="checkbox" name="terms" value="1" x-ref="termsCheck" @checked(old('terms')) class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-600 bg-slate-900 text-lime-300 focus:ring-lime-300/30"><span>I have read and agree to the <button type="button" @click="termsOpen = true" class="font-semibold text-lime-300 underline decoration-lime-300/40 underline-offset-2 hover:text-lime-200">Terms and Conditions</button>.</span></label>@error('terms')<p class="text-sm text-red-300">{{ $message }}</p>@enderror<p class="text-center text-sm text-slate-400">Already registered? <a href="{{ route('login') }}" class="font-semibold text-lime-300 hover:text-lime-200">Sign in</a></p></form>

{{-- Terms and Conditions pop-up --}}
<div x-cloak x-show="termsOpen" x-transition.opacity
     x-effect="document.body.style.overflow = termsOpen ? 'hidden' : ''"
     class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 backdrop-blur-sm sm:items-center sm:p-6"
     style="overflow-y: auto; -webkit-overflow-scrolling: touch;"
     @keydown.escape.window="termsOpen = false">
    <div @click.outside="termsOpen = false" x-show="termsOpen" x-transition
         class="flex w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl border border-white/10 bg-[#050505] shadow-2xl shadow-black/60 sm:rounded-2xl"
         style="max-height: 85vh; max-height: 90dvh;">
        <div class="flex shrink-0 items-center justify-between gap-2 border-b border-white/[.08] bg-[#0b0f14] px-3 py-2.5 sm:items-start sm:gap-3 sm:px-4 sm:py-3">
            <div class="min-w-0">
                <p class="text-[9px] font-bold uppercase tracking-[.16em] text-lime-300/80">Terms and Conditions</p>
                <p class="mt-0.5 text-[10px] text-slate-500">Updated {{ now()->format('M j, Y') }}</p>
            </div>
            <button type="button" @click="termsOpen = false" aria-label="Close terms and conditions" class="shrink-0 rounded-md border border-white/10 px-2.5 py-1 text-[13px] leading-none text-slate-400 transition hover:border-white/25 hover:text-white">&times;</button>
        </div>

        <div class="min-h-0 flex-1 px-2.5 py-2.5 sm:px-4 sm:py-3"
             style="overflow-y: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; touch-action: pan-y;">
            <p class="mb-2.5 break-words text-[11px] leading-5 text-slate-400 sm:mb-3">
                Rules for using the {{ config('reservation.facility_name') }} online reservation system.
                By creating an account or booking a court, you agree to them. Tap a heading to read the details.
            </p>
            @include('public.partials.terms-content')
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-x-3 gap-y-2 border-t border-white/[.08] bg-[#0b0f14] px-3 py-2.5 sm:px-4 sm:py-3">
            <button type="button" @click="termsOpen = false; $refs.termsCheck.checked = true" class="rounded-md bg-lime-300 px-3.5 py-1.5 text-[10px] font-black uppercase tracking-[.1em] text-slate-950 transition hover:bg-lime-200">I agree</button>
            <button type="button" @click="termsOpen = false" class="text-[11px] font-semibold text-slate-400 transition hover:text-white">Close</button>
            <a href="{{ route('terms') }}" target="_blank" rel="noopener" class="ml-auto text-[10px] font-semibold uppercase tracking-[.1em] text-slate-500 transition hover:text-lime-300">Full page</a>
        </div>
    </div>
</div>
</div>
@endsection
