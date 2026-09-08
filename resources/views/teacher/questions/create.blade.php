@extends('layouts.app')

@section('title', 'Nueva pregunta')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Nueva pregunta</h1>
        <p class="page-header__subtitle">{{ $activity->title }}</p>
    </div>

    <x-ui.card>
        <x-academic.question-form
            :action="route('teacher.activities.questions.store', $activity)"
            method="POST"
            :activity="$activity"
            :types="$types"
        />
    </x-ui.card>
@endsection
