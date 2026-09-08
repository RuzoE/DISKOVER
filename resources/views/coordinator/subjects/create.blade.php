@extends('layouts.app')

@section('title', 'Nueva asignatura')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Nueva asignatura</h1>
        <p class="page-header__subtitle">Curso: {{ $course->name }}</p>
    </div>

    <x-ui.card>
        <x-academic.subject-form
            :action="route('coordinator.courses.subjects.store', $course)"
            method="POST"
            :course="$course"
            :teachers="$teachers"
            :statuses="$statuses"
        />
    </x-ui.card>
@endsection
