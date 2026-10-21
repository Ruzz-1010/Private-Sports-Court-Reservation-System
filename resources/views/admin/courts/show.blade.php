@extends('layouts.admin')

@section('content')
<x-page-heading eyebrow="Court details" :title="$court->name" :description="$court->location"><a href="{{ route('admin.courts.index') }}" class="button button-quiet">← All courts</a><a href="{{ route('admin.courts.edit', $court) }}" class="button button-primary">Edit court</a></x-page-heading>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <x-court-photo :court="$court" class="mb-6 h-64" />

        <div class="border border-slate-700 surface p-6">
            <h2 class="font-semibold text-slate-100">Details</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-400">Hourly rate</dt>
                    <dd class="mt-1 font-medium text-slate-100">₱{{ number_format((float) $court->hourly_rate, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-400">Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $court->is_active ? 'bg-emerald-400/10 text-emerald-300' : 'surface-raised text-slate-300' }}">
                            {{ $court->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-slate-400">Description</dt>
                    <dd class="mt-1 text-slate-100">{{ $court->description ?: 'No description provided.' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-400">Total reservations</dt>
                    <dd class="mt-1 font-medium text-slate-100">{{ $court->reservations_count }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-400">Created</dt>
                    <dd class="mt-1 text-slate-100">{{ $court->created_at->format('M j, Y') }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-6 border border-slate-700 surface p-6">
            <h2 class="font-semibold text-slate-100">Recent reservations</h2>
            @if ($reservations->isEmpty())
                <p class="mt-3 text-sm text-slate-400">No reservations yet.</p>
            @else
                <div class="mt-4 table-scroll rounded-md border border-slate-700">
                    <table class="min-w-full divide-y divide-slate-700/60">
                        <caption class="sr-only">Ten most recent active reservations for this court</caption>
                        <thead class="surface-muted">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">Reference</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">Customer</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">Schedule</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/60">
                            @foreach ($reservations as $reservation)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-100">{{ $reservation->reference_number }}</td>
                                    <td class="px-3 py-2 text-sm text-slate-400">{{ $reservation->user?->name }}</td>
                                    <td class="px-3 py-2 text-sm text-slate-400">
                                        {{ $reservation->starts_at->format('M j, Y g:i A') }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                                     {{ $reservation->status->value === 'confirmed' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-amber-400/10 text-amber-300' }}">
                                            {{ ucfirst($reservation->status->value) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div>
        <div class="border border-slate-700 surface p-6">
            <h2 class="font-semibold text-slate-100">Danger zone</h2>
            @if ($hasHistory)
                <p class="mt-3 text-sm text-slate-400">
                    This court has existing reservations. Deleting it will
                    <span class="font-medium text-slate-100">archive</span> the court instead of
                    removing it, so booking, payment, and receipt history stay intact.
                </p>
            @else
                <p class="mt-3 text-sm text-slate-400">
                    This court has no reservations or blocks, so it can be removed permanently.
                </p>
            @endif

            <form method="POST" action="{{ route('admin.courts.destroy', $court) }}" class="mt-4"
                  onsubmit="return confirm('Are you sure you want to delete {{ $court->name }}? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="button button-danger w-full">
                    {{ $hasHistory ? 'Archive court' : 'Delete court' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
