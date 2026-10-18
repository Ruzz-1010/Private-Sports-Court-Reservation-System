@extends('layouts.admin')

@section('content')
@php
    $editing = isset($court);
@endphp

<div class="mx-auto max-w-2xl">
    <h1 class="text-2xl font-semibold text-slate-900">
        {{ $editing ? 'Edit court' : 'Add a court' }}
    </h1>
    <p class="mt-1 text-sm text-slate-600">
        {{ $editing ? 'Update the court details below.' : 'These details appear on the availability calendar and receipt.' }}
    </p>

    @include('admin.courts._form', ['court' => $court ?? null, 'editing' => $editing])
</div>
@endsection
