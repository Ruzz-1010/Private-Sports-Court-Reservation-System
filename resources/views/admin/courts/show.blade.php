@extends('layouts.admin')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">{{ $court->name }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $court->location }}</p>
    </div>
    <div class="flex items-center gap-3 text-sm">
        <a href="{{ route('admin.courts.edit', $court) }}"
           class="rounded-md border border-slate-300 px-4 py-2 font-medium text-slate-700 hover:bg-slate-50">Edit</a>
        <a href="{{ route('admin.courts.index') }}" class="text-slate-600 hover:text-slate-900">Back to list</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <x-court-photo :court="$court" class="mb-6 h-64" />

        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-slate-900">Details</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Hourly rate</dt>
                    <dd class="mt-1 font-medium text-slate-900">₱{{ number_format((float) $court->hourly_rate, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                     {{ $court->is_active ? 'bg-green-50 text-green-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $court->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-slate-500">Description</dt>
                    <dd class="mt-1 text-slate-900">{{ $court->description ?: 'No description provided.' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Total reservations</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $court->reservations_count }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Created</dt>
                    <dd class="mt-1 text-slate-900">{{ $court->created_at->format('M j, Y') }}</dd>
                </div>
            </dl>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-slate-900">Recent reservations</h2>
            @if ($reservations->isEmpty())
                <p class="mt-3 text-sm text-slate-600">No reservations yet.</p>
            @else
                <div class="mt-4 overflow-hidden rounded-md border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <caption class="sr-only">Ten most recent active reservations for this court</caption>
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Reference</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Customer</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Schedule</th>
                                <th scope="col" class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($reservations as $reservation)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-900">{{ $reservation->reference_number }}</td>
                                    <td class="px-3 py-2 text-sm text-slate-600">{{ $reservation->user?->name }}</td>
                                    <td class="px-3 py-2 text-sm text-slate-600">
                                        {{ $reservation->starts_at->format('M j, Y g:i A') }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                                     {{ $reservation->status->value === 'confirmed' ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-800' }}">
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
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-slate-900">Danger zone</h2>
            @if ($hasHistory)
                <p class="mt-3 text-sm text-slate-600">
                    This court has existing reservations. Deleting it will
                    <span class="font-medium text-slate-900">archive</span> the court instead of
                    removing it, so booking, payment, and receipt history stay intact.
                </p>
            @else
                <p class="mt-3 text-sm text-slate-600">
                    This court has no reservations or blocks, so it can be removed permanently.
                </p>
            @endif

            <form method="POST" action="{{ route('admin.courts.destroy', $court) }}" class="mt-4"
                  onsubmit="return confirm('Are you sure you want to delete {{ $court->name }}? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    {{ $hasHistory ? 'Archive court' : 'Delete court' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
