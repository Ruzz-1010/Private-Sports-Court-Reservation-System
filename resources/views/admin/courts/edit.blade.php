@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="text-2xl font-semibold text-slate-900">Edit court</h1>
    <p class="mt-1 text-sm text-slate-600">Update {{ $court->name }}.</p>

    @include('admin.courts._form', ['court' => $court, 'editing' => true])
</div>
@endsection
