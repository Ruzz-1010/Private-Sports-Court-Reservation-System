<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#f7f8fc] font-sans text-slate-900 antialiased">

@php
    // Guest pages have no session, so skip the nav and its database lookup.
    $showNav = auth()->check() && ! auth()->user()->isAdmin();

    $firstCourt = $showNav
        ? \App\Models\Court::query()->active()->orderBy('name')->value('id')
        : null;

    $nav = [
        ['label' => 'Dashboard', 'route' => 'customer.dashboard', 'url' => route('customer.dashboard'), 'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10'],
        ['label' => 'Book a court', 'route' => 'customer.availability.index', 'url' => $firstCourt ? route('customer.availability.index', ['court' => $firstCourt]) : null, 'icon' => 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
        ['label' => 'My reservations', 'route' => 'customer.reservations.index', 'url' => route('customer.reservations.index'), 'icon' => 'M9 12h6m-6 4h6M9 8h6M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z'],
    ];
@endphp

<div class="min-h-full">

    {{-- Top navigation --}}
    <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
        <nav class="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6" aria-label="Main">
            <div class="flex items-center gap-6">
                <a href="{{ route('customer.dashboard') }}" class="flex shrink-0 items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-950 text-xs font-black text-teal-300 shadow-lg shadow-slate-300">SC</span>
                    <span class="hidden text-sm font-black tracking-tight text-slate-950 md:block">San Jose Courts</span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    @foreach ($showNav ? $nav : [] as $item)
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}"
                               @if (request()->routeIs($item['route'])) aria-current="page" @endif
                               class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium
                                      {{ request()->routeIs($item['route'])
                                          ? 'bg-slate-950 text-white'
                                          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                </svg>
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                @auth
                    <span class="hidden text-sm text-slate-600 xl:block">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                            Log out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800">
                        Register
                    </a>
                @endauth
            </div>
        </nav>

        {{-- Mobile nav --}}
        @if ($showNav)
            <div class="flex gap-1 overflow-x-auto border-t border-slate-200 px-4 py-2 md:hidden">                @foreach ($nav as $item)
                @if ($item['url'])
                    <a href="{{ $item['url'] }}"
                       @if (request()->routeIs($item['route'])) aria-current="page" @endif
                       class="shrink-0 rounded-md px-3 py-1.5 text-sm font-medium
                              {{ request()->routeIs($item['route']) ? 'bg-slate-900 text-white' : 'text-slate-600' }}">
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
            </div>
        @endif
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
        @if (session('status'))
            <div role="status" class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-medium">Please fix the following:</p>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Only @yield is used here; $slot is reserved for Blade components. --}}
        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
