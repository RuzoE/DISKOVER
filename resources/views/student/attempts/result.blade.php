@extends('layouts.app')

@section('title', 'Resultado del intento')

@php
    $evaluation = $attempt->evaluation;
    $activity = $evaluation->activity;
    $answers = $attempt->answers->keyBy('question_id');
    $pct = $attempt->percentage();
    $passed = $evaluation->pass_score !== null && $pct !== null ? $pct >= (float) $evaluation->pass_score : null;
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Resultado · {{ $activity->title }}</h1>
            <p class="page-header__subtitle">
                Intento #{{ $attempt->number }} ·
                <x-ui.badge :color="$attempt->status->badgeColor()">{{ $attempt->status->label() }}</x-ui.badge>
            </p>
        </div>
        <x-ui.button :href="route('student.activities.show', $activity)" variant="ghost">Volver a la actividad</x-ui.button>
    </div>

    <x-ui.card>
        <p class="grade-big">
            {{ $attempt->score !== null ? rtrim(rtrim($attempt->score, '0'), '.') : '—' }}
            <span>/ {{ $attempt->max_score !== null ? rtrim(rtrim($attempt->max_score, '0'), '.') : '—' }}</span>
        </p>
        @if ($pct !== null)
            <p class="text-muted">{{ $pct }}%
                @if ($passed === true) <x-ui.badge color="green">Aprobado</x-ui.badge>
                @elseif ($passed === false) <x-ui.badge color="red">No alcanzado</x-ui.badge> @endif
            </p>
        @endif
        @if ($attempt->status === App\Enums\AttemptStatus::Submitted)
            <p class="text-muted u-mt-4">Hay preguntas abiertas pendientes de revisión del docente. La nota puede cambiar.</p>
        @endif
    </x-ui.card>

    @foreach ($evaluation->questions as $question)
        @php $answer = $answers->get($question->id); @endphp
        <x-ui.card>
            <p class="question-list__statement">{{ $loop->iteration }}. {{ $question->statement }}</p>
            <p class="text-muted">
                {{ $answer?->score_awarded !== null ? rtrim(rtrim($answer->score_awarded, '0'), '.') : '—' }}
                / {{ rtrim(rtrim($question->score, '0'), '.') }} pt
                @if ($answer?->is_correct === true) <x-ui.badge color="green">Correcta</x-ui.badge>
                @elseif ($answer?->is_correct === false) <x-ui.badge color="red">Incorrecta</x-ui.badge> @endif
            </p>

            @if ($question->type === App\Enums\QuestionType::Open)
                <div class="answer-box">{{ $answer?->text_answer ?: '(sin respuesta)' }}</div>
            @else
                <ul class="review-options">
                    @foreach ($question->options as $option)
                        <li class="{{ $option->is_correct ? 'is-correct' : '' }} {{ in_array($option->id, (array) $answer?->selected_option_ids, true) ? 'is-selected' : '' }}">
                            {{ $option->text }}
                            @if (in_array($option->id, (array) $answer?->selected_option_ids, true)) <strong>← tu respuesta</strong> @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    @endforeach
@endsection
