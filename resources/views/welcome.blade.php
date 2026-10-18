@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="mx-auto max-w-3xl text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Barangay San Jose · Puerto Princesa City, Palawan
        </p>
        <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
            Reserve your court in minutes
        </h1>
        <p class="mt-6 text-lg text-slate-600">
            Check live availability, book a basketball, badminton, or volleyball court,
            and pay online or on-site. Your receipt arrives by email the moment payment
            is verified.
        </p>

        <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
            @auth
                <a href="{{ route('dashboard') }}"
                   class="w-full rounded-md bg-slate-900 px-6 py-3 font-medium text-white hover:bg-slate-700 sm:w-auto">
                    Go to dashboard
                </a>
            @else
                <a href="{{ route('register') }}"
                   class="w-full rounded-md bg-slate-900 px-6 py-3 font-medium text-white hover:bg-slate-700 sm:w-auto">
                    Get started
                </a>
                <a href="{{ route('login') }}"
                   class="w-full rounded-md border border-slate-300 px-6 py-3 font-medium text-slate-900 hover:bg-slate-50 sm:w-auto">
                    Log in
                </a>
            @endauth
        </div>
    </div>

    @guest
        <div class="mx-auto mt-16 max-w-3xl">
            <h2 class="text-center text-sm font-semibold uppercase tracking-wide text-slate-500">
                Available courts
            </h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (\App\Models\Court::query()->active()->orderBy('name')->get() as $court)
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <x-court-photo :court="$court" class="h-40 rounded-none border-0" />
                        <div class="p-5">
                            <h3 class="font-semibold text-slate-900">{{ $court->name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $court->location }}</p>
                            <p class="mt-3 text-sm font-medium text-slate-900">
                                ₱{{ number_format((float) $court->hourly_rate, 2) }} / hour
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endguest
</div>
@endsection
