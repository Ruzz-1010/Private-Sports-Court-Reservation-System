@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Pickleball courts</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $courts->total() }} {{ Str::plural('pickleball court', $courts->total()) }} in the facility.</p>
    </div>
    <a href="{{ route('admin.courts.create') }}"
       class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
        Add pickleball court
    </a>
</div>

<form method="GET" action="{{ route('admin.courts.index') }}" class="mb-6 flex gap-2">
    <label for="search" class="sr-only">Search courts</label>
    <input id="search" name="search" type="search" value="{{ $search }}"
           placeholder="Search by court name"
           class="w-full max-w-xs rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
    <button type="submit" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
        Search
    </button>
    @if ($search !== '')
        <a href="{{ route('admin.courts.index') }}" class="rounded-md px-3 py-2 text-sm text-slate-600 hover:text-slate-900">Clear</a>
    @endif
</form>

<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <table class="w-full min-w-[56rem] divide-y divide-slate-200">
        <caption class="sr-only">List of all courts with rate, status, and reservation count</caption>
        <thead class="bg-slate-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Court</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Location</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Rate / hour</th>
                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Reservations</th>
                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($courts as $court)
                <tr class="{{ $court->trashed() ? 'bg-slate-50 text-slate-500' : '' }}">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <x-court-photo :court="$court" class="h-10 w-14 shrink-0 rounded-md" />
                            <div>
                                <a href="{{ route('admin.courts.show', $court) }}" class="font-medium text-slate-900 hover:underline">
                                    {{ $court->name }}
                                </a>
                                @if ($court->trashed())
                                    <span class="block text-xs text-slate-500">archived</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $court->location }}</td>
                    <td class="px-4 py-3 text-right text-sm text-slate-900">₱{{ number_format((float) $court->hourly_rate, 2) }}</td>
                    <td class="px-4 py-3 text-center text-sm text-slate-600">{{ $court->reservations_count }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                     {{ $court->is_active ? 'bg-green-50 text-green-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $court->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm">
                        @if ($court->trashed())
                            <form method="POST" action="{{ route('admin.courts.restore', $court->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-slate-700 hover:text-slate-900">Restore</button>
                            </form>
                        @else
                            <a href="{{ route('admin.courts.edit', $court) }}" class="text-slate-700 hover:text-slate-900">Edit</a>
                            <span class="mx-2 text-slate-300">|</span>
                            <form method="POST" action="{{ route('admin.courts.destroy', $court) }}" class="inline"
                                  onsubmit="return confirm('Delete {{ $court->name }}? A court with existing reservations will be archived instead.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-600">
                        No courts found. Add your first court to get started.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($courts->hasPages())
    <div class="mt-6">{{ $courts->links() }}</div>
@endif
@endsection
