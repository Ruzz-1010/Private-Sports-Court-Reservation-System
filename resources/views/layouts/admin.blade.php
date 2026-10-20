<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}Admin · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell min-h-full bg-[#070a0f] font-sans text-slate-100 antialiased">
@php
    $nav = [
        ['label'=>'Overview','route'=>'admin.dashboard','url'=>route('admin.dashboard'),'icon'=>'M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z'],
        ['label'=>'Courts','route'=>'admin.courts.*','url'=>route('admin.courts.index'),'icon'=>'M4 6h16M4 12h16M4 18h16'],
        ['label'=>'Blocks','route'=>'admin.court-blocks.*','url'=>route('admin.court-blocks.index'),'icon'=>'M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z'],
        ['label'=>'Reservations','route'=>'admin.reservations.*','url'=>route('admin.reservations.index'),'icon'=>'M5 4h14a2 2 0 012 2v14H5a2 2 0 01-2-2V6a2 2 0 012-2Zm4 4h6m-6 4h6m-6 4h4'],
        ['label'=>'Payments','route'=>'admin.payments.*','url'=>route('admin.payments.index'),'icon'=>'M3 7h18v10H3V7Zm0 4h18'],
        ['label'=>'Reports','route'=>'admin.reports.*','url'=>route('admin.reports.index'),'icon'=>'M4 19V5m0 14h16M8 16V9m4 7v-4m4 4V7'],
    ];
@endphp
<header class="sticky top-0 z-40 border-b border-white/10 bg-[#070a0f]/95 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-[90rem] items-center gap-5 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('admin.dashboard') }}" class="flex shrink-0 items-center gap-3">
            <img src="{{ asset('images/san-jose-pickleball-logo.png') }}" alt="San Jose Pickleball" class="h-9 w-9 object-contain">
            <span class="hidden sm:block"><span class="block text-sm font-bold leading-none text-white">San Jose Pickleball</span><span class="mt-1 block text-[10px] font-bold uppercase tracking-[.18em] text-lime-300">Admin console</span></span>
        </a>
        <nav class="hidden min-w-0 flex-1 items-center justify-center gap-1 lg:flex" aria-label="Admin navigation">
            @foreach($nav as $item) @php($active=request()->routeIs($item['route']))
                <a href="{{ $item['url'] }}" @if($active) aria-current="page" @endif class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $active ? 'bg-lime-300 text-slate-950' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex items-center gap-3">
            <div class="hidden text-right md:block"><p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p><p class="text-xs text-slate-500">Administrator</p></div>
            <span class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/5 text-xs font-bold text-lime-300">{{ Str::upper(Str::substr(auth()->user()->name ?? 'A',0,1)) }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="rounded-lg border border-white/10 px-3 py-2 text-sm font-semibold text-slate-300 transition hover:bg-white/5 hover:text-white">Log out</button></form>
        </div>
    </div>
    <nav class="flex gap-1 overflow-x-auto border-t border-white/10 px-4 py-2 lg:hidden" aria-label="Mobile admin navigation">
        @foreach($nav as $item) @php($active=request()->routeIs($item['route']))<a href="{{ $item['url'] }}" class="shrink-0 rounded-md px-3 py-1.5 text-sm font-semibold {{ $active ? 'bg-lime-300 text-slate-950' : 'text-slate-400' }}">{{ $item['label'] }}</a>@endforeach
    </nav>
</header>
<main class="mx-auto min-h-[calc(100vh-4rem)] max-w-[90rem] px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
    @if(session('status'))<div role="status" class="mb-6 rounded-xl border border-lime-300/25 bg-lime-300/10 px-4 py-3 text-sm text-lime-100">{{ session('status') }}</div>@endif
    @if($errors->any())<div role="alert" class="mb-6 rounded-xl border border-red-400/25 bg-red-400/10 px-4 py-3 text-sm text-red-100"><p class="font-semibold">Please fix the following:</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
</body>
</html>
