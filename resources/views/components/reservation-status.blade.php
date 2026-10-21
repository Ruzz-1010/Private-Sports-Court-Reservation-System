@props(['status'])
@php
    $style = match ($status) {
        'confirmed', 'completed' => 'badge-success',
        'pending' => 'badge-warning',
        'cancelled' => 'badge-danger',
        default => '',
    };
@endphp
<span {{ $attributes->class(['badge', $style]) }}>{{ ucfirst($status) }}</span>
