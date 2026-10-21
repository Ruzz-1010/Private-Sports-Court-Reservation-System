@extends('layouts.admin')

@section('content')
<x-page-heading eyebrow="Facility management" title="Your courts" :description="$courts->total().' '.Str::plural('court', $courts->total()).' in your facility. Manage rates, photos, and availability.'"><a href="{{ route('admin.courts.create') }}" class="button button-primary"><span aria-hidden="true">＋</span> Add a court</a></x-page-heading>

<form method="GET" action="{{ route('admin.courts.index') }}" class="filter-panel">
    <label for="search" class="sr-only">Search courts</label>
    <input id="search" name="search" type="search" value="{{ $search }}"
           placeholder="Search by court name"
           class="w-full max-w-xs rounded-md border border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none">
    <button type="submit" class="button">
        Search
    </button>
    @if ($search !== '')
        <a href="{{ route('admin.courts.index') }}" class="rounded-md px-3 py-2 text-sm text-slate-400 hover:text-white">Clear</a>
    @endif
</form>

<div class="table-scroll border border-slate-700 surface">
    <table class="w-full min-w-[56rem] divide-y divide-slate-700/60">
        <caption class="sr-only">List of all courts with rate, status, and reservation count</caption>
        <thead class="surface-muted">
            <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">Court</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">Location</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-400">Rate / hour</th>
                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">Reservations</th>
                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">Status</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-400">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/60">
            @forelse ($courts as $court)
                <tr class="{{ $court->trashed() ? 'surface-muted text-slate-400' : '' }}">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <x-court-photo :court="$court" class="h-10 w-14 shrink-0 rounded-md" />
                            <div>
                                <a href="{{ route('admin.courts.show', $court) }}" class="font-medium text-slate-100 hover:underline">
                                    {{ $court->name }}
                                </a>
                                @if ($court->trashed())
                                    <span class="block text-xs text-slate-400">archived</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-400">{{ $court->location }}</td>
                    <td class="px-4 py-3 text-right text-sm text-slate-100">₱{{ number_format((float) $court->hourly_rate, 2) }}</td>
                    <td class="px-4 py-3 text-center text-sm text-slate-400">{{ $court->reservations_count }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $court->is_active ? 'bg-emerald-400/10 text-emerald-300' : 'surface-raised text-slate-300' }}">
                            {{ $court->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm">
                        @if ($court->trashed())
                            <form method="POST" action="{{ route('admin.courts.restore', $court->id) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-slate-300 hover:text-white">Restore</button>
                            </form>
                        @else
                            <a href="{{ route('admin.courts.edit', $court) }}" class="text-slate-300 hover:text-white">Edit</a>
                            <span class="mx-2 text-slate-300">|</span>
                            <form method="POST" action="{{ route('admin.courts.destroy', $court) }}" class="inline"
                                  onsubmit="return confirm('Delete {{ $court->name }}? A court with existing reservations will be archived instead.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-300 hover:text-rose-200">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">
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
