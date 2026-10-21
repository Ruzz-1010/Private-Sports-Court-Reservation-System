@php
    $isAdminWorkspace = $isAdminWorkspace ?? false;
    $workspaceUser = auth()->user();
    $firstCourtId = $isAdminWorkspace ? null : \App\Models\Court::query()->active()->orderBy('name')->value('id');
    $workspaceNav = $isAdminWorkspace ? [
        ['Overview', 'admin.dashboard', route('admin.dashboard'), 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z'],
        ['Courts', 'admin.courts.*', route('admin.courts.index'), 'M3 5h18v14H3zM12 5v14M3 12h18'],
        ['Schedule blocks', 'admin.court-blocks.*', route('admin.court-blocks.index'), 'M8 3v4m8-4v4M3 11h18M5 5h14v16H5zM9 15h6'],
        ['Reservations', 'admin.reservations.*', route('admin.reservations.index'), 'M8 3h8v4H8zM6 5H4v16h16V5h-2M8 12h8m-8 4h5'],
        ['Payments', 'admin.payments.*', route('admin.payments.index'), 'M3 6h18v12H3zM3 10h18m-5 4h2'],
        ['Reports', 'admin.reports.*', route('admin.reports.index'), 'M4 3v18h17M8 16v-5m5 5V7m5 9v-8'],
    ] : [
        ['Overview', 'customer.dashboard', route('customer.dashboard'), 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z'],
        ['Book a court', 'customer.availability.*', $firstCourtId ? route('customer.availability.index', ['court' => $firstCourtId]) : route('customer.dashboard'), 'M8 3v4m8-4v4M3 11h18M5 5h14v16H5zM9 15h6'],
        ['My reservations', 'customer.reservations.*', route('customer.reservations.index'), 'M8 3h8v4H8zM6 5H4v16h16V5h-2M8 12h8m-8 4h5'],
    ];
    $workspaceSection = collect($workspaceNav)->first(fn ($item) => request()->routeIs($item[1]));
    $workspaceSectionName = request()->routeIs('customer.reservations.create') ? 'Book a court' : ($workspaceSection[0] ?? 'Overview');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark">
    <title>{{ $workspaceSectionName }} · {{ $isAdminWorkspace ? 'Admin' : 'Member' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="workspace {{ $isAdminWorkspace ? 'admin-shell' : 'customer-shell' }} font-sans antialiased">
    <a href="#workspace-content" class="skip-link">Skip to content</a>
    <aside class="workspace-sidebar">
        <a href="{{ route($isAdminWorkspace ? 'admin.dashboard' : 'customer.dashboard') }}" class="workspace-brand">
            <img src="{{ asset('images/san-jose-pickleball-logo.png') }}" alt="" width="42" height="42">
            <span>San Jose<span class="workspace-brand-subtitle">PICKLEBALL CLUB</span></span>
        </a>
        <p class="workspace-nav-label">{{ $isAdminWorkspace ? 'MANAGEMENT' : 'YOUR CLUB' }}</p>
        <nav class="workspace-navigation" aria-label="{{ $isAdminWorkspace ? 'Admin' : 'Customer' }} navigation">
            @foreach ($workspaceNav as [$label, $pattern, $url, $icon])
                @php($active = request()->routeIs($pattern) || ($label === 'Book a court' && request()->routeIs('customer.reservations.create')))
                <a href="{{ $url }}" class="workspace-nav-link {{ $active && !($label === 'My reservations' && request()->routeIs('customer.reservations.create')) ? 'is-active' : '' }}" @if($active && !($label === 'My reservations' && request()->routeIs('customer.reservations.create'))) aria-current="page" @endif>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
        <div class="workspace-sidebar-note">
            <span class="workspace-kicker">{{ $isAdminWorkspace ? 'FACILITY WORKSPACE' : 'READY FOR YOUR NEXT GAME?' }}</span>
            <p>{{ $isAdminWorkspace ? 'Everything you need to keep the courts running smoothly.' : 'Find an open court and make time for play.' }}</p>
            <a href="{{ $isAdminWorkspace ? route('admin.courts.index') : ($firstCourtId ? route('customer.availability.index', ['court' => $firstCourtId]) : route('customer.dashboard')) }}">{{ $isAdminWorkspace ? 'Manage courts' : 'Explore availability' }} <span aria-hidden="true">↗</span></a>
        </div>
        <div class="workspace-account">
            <span class="workspace-avatar">{{ Str::upper(Str::substr($workspaceUser->name, 0, 1)) }}</span>
            <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-100">{{ $workspaceUser->name }}</span><span class="text-xs text-slate-400">{{ $isAdminWorkspace ? 'Administrator' : 'Club member' }}</span></span>
        </div>
    </aside>
    <div class="workspace-body">
        <header class="workspace-topbar">
            <a href="{{ route($isAdminWorkspace ? 'admin.dashboard' : 'customer.dashboard') }}" class="workspace-mobile-brand" aria-label="San Jose Pickleball overview"><img src="{{ asset('images/san-jose-pickleball-logo.png') }}" alt="" width="32" height="32"></a>
            <div class="workspace-breadcrumb"><span>{{ $isAdminWorkspace ? 'Admin console' : 'Member space' }}</span><span aria-hidden="true">/</span><strong>{{ $workspaceSectionName }}</strong></div>
            <div class="workspace-topbar-actions">
                <time class="workspace-today" datetime="{{ now(config('reservation.timezone'))->toDateString() }}">{{ now(config('reservation.timezone'))->format('D, M j') }} <span>· Manila</span></time>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="button button-quiet">Log out <span aria-hidden="true">↗</span></button></form>
            </div>
        </header>
        <nav class="workspace-mobile-nav" aria-label="Mobile {{ $isAdminWorkspace ? 'admin' : 'customer' }} navigation">
            @foreach ($workspaceNav as [$label, $pattern, $url, $icon])
                @php($active = ($label === 'Book a court' && request()->routeIs('customer.reservations.create')) || (request()->routeIs($pattern) && !($label === 'My reservations' && request()->routeIs('customer.reservations.create'))))
                <a href="{{ $url }}" @if($active) aria-current="page" @endif class="{{ $active ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <main id="workspace-content" class="workspace-content" tabindex="-1">
            @if (session('status'))<div role="status" class="notice notice-success mb-6">{{ session('status') }}</div>@endif
            @if ($errors->any())
                <div role="alert" class="notice notice-danger mb-6"><p class="font-semibold">Please review the following</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
        <footer class="workspace-footer"><span>San Jose Pickleball Club</span><span>Puerto Princesa, Palawan</span></footer>
    </div>
    @stack('scripts')
</body>
</html>
