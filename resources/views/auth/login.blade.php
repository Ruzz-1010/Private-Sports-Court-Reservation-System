@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md">
    <h1 class="mb-6 text-2xl font-semibold text-slate-900">Log in</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username"
                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input id="remember" name="remember" type="checkbox"
                   class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
            <label for="remember" class="text-sm text-slate-700">Remember me</label>
        </div>

        <button type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 font-medium text-white hover:bg-slate-700">
            Log in
        </button>

        <p class="text-center text-sm text-slate-600">
            No account yet?
            <a href="{{ route('register') }}" class="font-medium text-slate-900 underline">Register</a>
        </p>
    </form>
</div>
@endsection
