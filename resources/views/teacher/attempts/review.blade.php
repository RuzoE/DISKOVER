@extends('layouts.app')

@section('title', 'Revisar intento')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Revisar intento #{{ $attempt->number }}</h1>
            <p class="page-header__subtitle">
                {{ $attempt->student->name }} · {{ $attempt->evaluation->activity->title }} ·
                <x-ui.badge :color="$attempt->status->badgeColor()">{{ $attempt->status->label() }}</x-ui.badge>
            </p>
        </div>
        <x-ui.button :href="route('teacher.activities.gradebook', $attempt->evaluation->activity_id)" variant="ghost">Volver al cuaderno</x-ui.button>
    </div>

    <x-ui.card title="Resumen">
        <dl class="detail-list">
            <div class="detail-list__row"><dt>Puntuación</dt>
                <dd>{{ $attempt->score !== null ? rtrim(rtrim($attempt->score, '0'), '.') : '—' }} / {{ $attempt->max_score !== null ? rtrim(rtrim($attempt->max_score, '0'), '.') : '—' }}
                    @if ($attempt->percentage() !== null) ({{ $attempt->percentage() }}%) @endif
                </dd>
            </div>
            <div class="detail-list__row"><dt>Enviado</dt><dd>{{ $attempt->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>
    </x-ui.card>

    <form method="POST" action="{{ route('teacher.attempts.review.update', $attempt) }}">
        @csrf @method('PUT')

        @foreach ($attempt->answers->sortBy(fn ($a) => $a->question->position) as $answer)
            @php $q = $answer->question; @endphp
            <x-ui.card>
                <p class="question-list__statement">{{ $loop->iteration }}. {{ $q->statement }}</p>
                <p class="text-muted"><x-ui.badge>{{ $q->type->label() }}</x-ui.badge> {{ rtrim(rtrim($q->score, '0'), '.') }} pt</p>

                @if ($q->type === App\Enums\QuestionType::Open)
                    <div class="answer-box">{{ $answer->text_answer ?: '(sin respuesta)' }}</div>
                    <div class="form__grid u-mt-4">
                        <x-ui.input type="number" name="scores[{{ $answer->id }}]" label="Puntos otorgados"
                                    min="0" max="{{ $q->score }}" step="0.25"
                                    :value="$answer->score_awarded" />
                    </div>
                @else
                    <ul class="review-options">
                        @foreach ($q->options as $option)
                            <li class="{{ $option->is_correct ? 'is-correct' : '' }} {{ in_array($option->id, (array) $answer->selected_option_ids, true) ? 'is-selected' : '' }}">
                                {{ $option->text }}
                                @if ($option->is_correct) <span class="text-muted">(correcta)</span> @endif
                                @if (in_array($option->id, (array) $answer->selected_option_ids, true)) <strong>← elegida</strong> @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-muted">Otorgado automáticamente: {{ $answer->score_awarded !== null ? rtrim(rtrim($answer->score_awarded, '0'), '.') : 0 }} pt</p>
                @endif
            </x-ui.card>
        @endforeach

        <div class="form__actions">
            <x-ui.button type="submit" variant="primary">Guardar revisión y calificar</x-ui.button>
        </div>
    </form>
@endsection
