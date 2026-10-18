<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="min-h-full">
        <header class="border-b border-slate-200 bg-white">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4" aria-label="Main">
                <a href="{{ route('home') }}" class="text-lg font-semibold text-slate-900">
                    {{ config('app.name') }}
                </a>

                <div class="flex items-center gap-4 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-slate-900">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-600 hover:text-slate-900">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Log in</a>
                        <a href="{{ route('register') }}"
                           class="rounded-md bg-slate-900 px-3 py-2 font-medium text-white hover:bg-slate-700">Register</a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            @isset($title)
                <h1 class="mb-6 text-2xl font-semibold text-slate-900">{{ $title }}</h1>
            @endisset

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

            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</body>
</html>
