@extends('layouts.app')

@section('title', 'Evaluación en curso')

@section('content')
    @php
        $evaluation = $attempt->evaluation;
        $activity = $evaluation->activity;
        $answers = $attempt->answers->keyBy('question_id');
        $deadline = $evaluation->time_limit_minutes
            ? $attempt->started_at->copy()->addMinutes($evaluation->time_limit_minutes)
            : null;
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $activity->title }}</h1>
            <p class="page-header__subtitle">Intento #{{ $attempt->number }} · {{ $activity->subject->name }}</p>
        </div>
        @if ($deadline)
            <p class="quiz-timer" data-quiz-deadline="{{ $deadline->toIso8601String() }}">
                Tiempo restante: <span data-quiz-remaining>--:--</span>
            </p>
        @endif
    </div>

    <form method="POST" action="{{ route('student.attempts.submit', $attempt) }}" data-quiz-form>
        @csrf

        @foreach ($evaluation->questions as $question)
            @php $current = $answers->get($question->id); @endphp
            <x-ui.card>
                <p class="question-list__statement">{{ $loop->iteration }}. {{ $question->statement }}
                    <span class="text-muted">({{ rtrim(rtrim($question->score, '0'), '.') }} pt)</span>
                </p>

                @if ($question->type === App\Enums\QuestionType::Open)
                    <textarea name="answers[{{ $question->id }}]" rows="5" class="field__control">{{ $current?->text_answer }}</textarea>
                @else
                    @php $selected = (array) ($current?->selected_option_ids ?? []); @endphp
                    <div class="quiz-options">
                        @foreach ($question->options as $option)
                            <label class="checkbox">
                                <input
                                    type="{{ $question->type === App\Enums\QuestionType::Multiple ? 'checkbox' : 'radio' }}"
                                    name="answers[{{ $question->id }}]{{ $question->type === App\Enums\QuestionType::Multiple ? '[]' : '' }}"
                                    value="{{ $option->id }}"
                                    @checked(in_array($option->id, $selected, true))
                                >
                                <span>{{ $option->text }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        @endforeach

        <div class="form__actions">
            <x-ui.button type="submit" variant="primary">Enviar intento</x-ui.button>
            <button type="submit" class="btn btn--secondary"
                    formaction="{{ route('student.attempts.save', $attempt) }}">
                Guardar y salir
            </button>
        </div>
    </form>
@endsection
