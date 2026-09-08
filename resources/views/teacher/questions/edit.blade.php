@extends('layouts.app')

@section('title', 'Editar pregunta')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Editar pregunta</h1>
        <p class="page-header__subtitle">{{ $question->evaluation->activity->title }}</p>
    </div>

    <x-ui.card>
        <x-academic.question-form
            :action="route('teacher.questions.update', $question)"
            method="PUT"
            :activity="$question->evaluation->activity"
            :question="$question"
            :types="$types"
        />
    </x-ui.card>
@endsection
