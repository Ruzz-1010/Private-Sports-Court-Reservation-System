@props(['status'])

@php
    // Colour is never the only signal: every badge carries a text label too.
    $styles = match ($status) {
        'pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'confirmed' => 'bg-green-50 text-green-800 ring-green-200',
        'completed' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'cancelled' => 'bg-red-50 text-red-800 ring-red-200',
        'expired' => 'bg-slate-100 text-slate-600 ring-slate-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset $styles"]) }}>
    {{ ucfirst($status) }}
</span>
