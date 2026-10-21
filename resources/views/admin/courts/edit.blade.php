@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-2xl">
    <x-page-heading eyebrow="Court management" title="Edit court" :description="'Update the details, rate, and photo for '.$court->name.'.'"><a href="{{ route('admin.courts.index') }}" class="text-link">← All courts</a></x-page-heading>

    @include('admin.courts._form', ['court' => $court, 'editing' => true])
</div>
@endsection
