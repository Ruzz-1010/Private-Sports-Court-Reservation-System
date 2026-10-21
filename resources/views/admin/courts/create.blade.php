@extends('layouts.admin')

@section('content')
@php
    $editing = isset($court);
@endphp

<div class="mx-auto max-w-2xl">
    <x-page-heading eyebrow="Court management" :title="$editing ? 'Edit court' : 'Add a court'" description="Set up a court that players will love. These details appear when customers book."><a href="{{ route('admin.courts.index') }}" class="text-link">← All courts</a></x-page-heading>

    @include('admin.courts._form', ['court' => $court ?? null, 'editing' => $editing])
</div>
@endsection
