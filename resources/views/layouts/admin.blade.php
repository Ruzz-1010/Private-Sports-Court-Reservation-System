<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Admin · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[radial-gradient(circle_at_100%_0%,_#eefdf9_0,_transparent_28%),radial-gradient(circle_at_0%_45%,_#eef2ff_0,_transparent_30%),#f7f8fc] font-sans text-slate-900 antialiased">

@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'url' => route('admin.dashboard'), 'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10'],
        ['label' => 'Pickleball courts', 'route' => 'admin.courts.index', 'url' => route('admin.courts.index'), 'icon' => 'M4 6h16M4 12h16M4 18h16'],
        ['label' => 'Schedule blocks', 'route' => 'admin.court-blocks.index', 'url' => route('admin.court-blocks.index'), 'icon' => 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
        ['label' => 'Reservations', 'route' => 'admin.reservations.index', 'url' => route('admin.reservations.index'), 'icon' => 'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
        ['label' => 'Payments', 'route' => 'admin.payments.index', 'url' => route('admin.payments.index'), 'icon' => 'M3 10h18M7 15h4'],
        ['label' => 'Reports', 'route' => 'admin.reports.index', 'url' => route('admin.reports.index'), 'icon' => 'M4 19V5m0 14h16M8 16V9m4 7v-4m4 4V7'],
    ];
@endphp

<div class="flex min-h-full">

    {{-- Sidebar --}}
    <aside class="sticky top-0 flex h-screen w-16 shrink-0 flex-col border-r border-slate-800 bg-[#0b1020] md:w-64">
        <div class="flex h-16 items-center gap-2 border-b border-slate-800 px-3 md:px-5">
            <img src="{{ asset('images/san-jose-pickleball-logo.png') }}" alt="San Jose Pickleball" class="h-10 w-10 shrink-0 object-contain">
            <span class="hidden text-sm font-semibold leading-tight text-white md:block">
                San Jose Pickleball
                <span class="block text-xs font-normal text-slate-400">Admin workspace</span>
            </span>
        </div>

        <nav class="flex-1 space-y-1 p-3" aria-label="Admin">
            @foreach ($nav as $item)
                @php $isActive = request()->routeIs($item['route']) && ! ($item['soon'] ?? false); @endphp
                @if ($item['soon'] ?? false)
                    <span aria-disabled="true" title="{{ $item['label'] }} (coming soon)"
                          class="flex cursor-not-allowed items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-500">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span class="hidden md:inline">{{ $item['label'] }}</span>
                        <span class="ml-auto hidden rounded bg-slate-800 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-slate-500 md:inline">Soon</span>
                    </span>
                @else
                    <a href="{{ $item['url'] ?? route($item['route']) }}" title="{{ $item['label'] }}"
                       @if ($isActive) aria-current="page" @endif
                       class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium
                              {{ $isActive ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                        </svg>
                        <span class="hidden md:inline">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-slate-800 p-3">
            <div class="flex items-center gap-3 rounded-md px-3 py-2">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-white">
                    {{ Str::upper(Str::substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </span>
                <span class="hidden min-w-0 flex-1 md:block">
                    <span class="block truncate text-sm font-medium text-white">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-xs text-slate-400">Administrator</span>
                </span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Log out"
                        class="mt-1 flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-400 hover:bg-slate-800 hover:text-white">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="hidden md:inline">Log out</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Content --}}
    <div class="flex min-w-0 flex-1 flex-col">

        <main class="flex-1 px-4 py-7 sm:px-6 lg:px-8 xl:px-10">
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

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
