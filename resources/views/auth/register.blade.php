@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md">
    <h1 class="mb-2 text-2xl font-semibold text-slate-900">Create your account</h1>
    <p class="mb-6 text-sm text-slate-600">
        Register to reserve a court at {{ config('app.name') }}.
    </p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                   autocomplete="name"
                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   autocomplete="username"
                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-slate-500">Your receipt and confirmation are sent here.</p>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password"
                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-slate-500 focus:outline-none">
            @error('password_confirmation')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full rounded-md bg-slate-900 px-4 py-2 font-medium text-white hover:bg-slate-700">
            Register
        </button>

        <p class="text-center text-sm text-slate-600">
            Already registered?
            <a href="{{ route('login') }}" class="font-medium text-slate-900 underline">Log in</a>
        </p>
    </form>
</div>
@endsection
