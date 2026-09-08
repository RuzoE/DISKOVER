@extends('layouts.app')

@section('title', 'Nueva actividad')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Nueva actividad</h1>
        <p class="page-header__subtitle">{{ $subject->name }} · {{ $subject->course->name }}</p>
    </div>

    <x-ui.card>
        <x-academic.activity-form
            :action="route('teacher.subjects.activities.store', $subject)"
            method="POST"
            :subject="$subject"
            :types="$types"
        />
    </x-ui.card>
@endsection
