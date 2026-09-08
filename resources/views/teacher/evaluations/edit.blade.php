@extends('layouts.app')

@section('title', 'Evaluación · '.$activity->title)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Evaluación: {{ $activity->title }}</h1>
            <p class="page-header__subtitle">{{ $activity->subject->name }} · {{ $activity->subject->course->name }}</p>
        </div>
        <x-ui.button :href="route('teacher.activities.show', $activity)" variant="ghost">Volver a la actividad</x-ui.button>
    </div>

    <x-ui.card title="Configuración">
        <form method="POST" action="{{ route('teacher.activities.evaluation.update', $activity) }}" class="form form--stacked">
            @csrf @method('PUT')
            <div class="form__grid">
                <x-ui.input type="number" name="time_limit_minutes" label="Tiempo límite (minutos)" min="1"
                            :value="$evaluation->time_limit_minutes" hint="Vacío = sin límite." />
                <x-ui.input type="number" name="max_attempts" label="Intentos permitidos" min="1"
                            :value="$evaluation->max_attempts" required />
            </div>
            <div class="form__grid">
                <x-ui.input type="number" name="pass_score" label="Nota de aprobado (%)" min="0" max="100" step="0.01"
                            :value="$evaluation->pass_score" />
                <label class="checkbox">
                    <input type="hidden" name="shuffle_questions" value="0">
                    <input type="checkbox" name="shuffle_questions" value="1" @checked($evaluation->shuffle_questions)>
                    <span>Barajar preguntas</span>
                </label>
            </div>
            <div class="form__actions">
                <x-ui.button type="submit" variant="primary">Guardar configuración</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Preguntas ({{ $evaluation->questions->count() }} · {{ $evaluation->totalScore() }} puntos)">
        <x-slot:actions>
            <x-ui.button :href="route('teacher.activities.questions.create', $activity)" variant="primary" class="btn--sm">Añadir pregunta</x-ui.button>
        </x-slot:actions>

        @if ($evaluation->questions->isEmpty())
            <x-tables.empty-state message="Sin preguntas. Añade la primera." />
        @else
            <ol class="question-list">
                @foreach ($evaluation->questions as $question)
                    <li class="question-list__item">
                        <div>
                            <p class="question-list__statement">{{ $question->statement }}</p>
                            <p class="text-muted">
                                <x-ui.badge>{{ $question->type->label() }}</x-ui.badge>
                                {{ rtrim(rtrim($question->score, '0'), '.') }} pt ·
                                {{ $question->options->count() }} opción(es)
                            </p>
                        </div>
                        <div class="table__actions">
                            <a href="{{ route('teacher.questions.edit', $question) }}" class="link">Editar</a>
                            <form method="POST" action="{{ route('teacher.questions.destroy', $question) }}"
                                  data-confirm="¿Eliminar esta pregunta?">
                                @csrf @method('DELETE')
                                <button type="submit" class="link link--danger">Eliminar</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-ui.card>
@endsection
