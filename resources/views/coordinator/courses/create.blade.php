@extends('layouts.app')

@section('title', 'Nuevo curso')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Nuevo curso</h1>
    </div>

    <x-ui.card>
        <x-academic.course-form :action="route('coordinator.courses.store')" method="POST" :statuses="$statuses" />
    </x-ui.card>
@endsection
