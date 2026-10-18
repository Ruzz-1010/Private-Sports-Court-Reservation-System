@props(['court', 'class' => 'h-40'])

@php
    // The size classes must come last so callers can override the defaults.
    $sizes = trim($class.' '.$attributes->get('class'));
@endphp

<div class="{{ $sizes }}">
    @if ($court->imageUrl())
        <img src="{{ $court->imageUrl() }}" alt="{{ $court->name }}"
             class="h-full w-full rounded-lg border border-slate-200 object-cover">
    @else
        <div class="flex h-full w-full items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-100 text-slate-500"
             role="img" aria-label="No photo uploaded for {{ $court->name }}">
            <span class="flex flex-col items-center gap-1">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                </svg>
                <span class="text-xs">No photo</span>
            </span>
        </div>
    @endif
</div>
