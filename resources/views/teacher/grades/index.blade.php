@extends('layouts.app')

@section('title', 'Cuaderno · '.$activity->title)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Cuaderno de calificaciones</h1>
            <p class="page-header__subtitle">
                {{ $activity->title }} · {{ $activity->subject->name }} ·
                máx. {{ rtrim(rtrim($activity->max_score, '0'), '.') }}
            </p>
        </div>
        <x-ui.button :href="route('teacher.activities.show', $activity)" variant="ghost">Volver</x-ui.button>
    </div>

    <x-ui.card>
        @if ($students->isEmpty())
            <x-tables.empty-state message="El curso no tiene estudiantes inscritos." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            @if ($activity->isQuiz())<th>Intentos</th>@endif
                            <th>Calificación</th>
                            <th>Origen</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            @php
                                $grade = $gradesByStudent->get($student->id);
                                $attempts = $attemptsByStudent->get($student->id, collect());
                                $pending = $attempts->firstWhere('status', App\Enums\AttemptStatus::Submitted);
                            @endphp
                            <tr>
                                <td>{{ $student->name }}</td>
                                @if ($activity->isQuiz())
                                    <td>
                                        {{ $attempts->count() }}
                                        @if ($pending)
                                            <x-ui.badge color="amber">Revisar</x-ui.badge>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    @if ($grade)
                                        <strong>{{ rtrim(rtrim($grade->score, '0'), '.') }}</strong> / {{ rtrim(rtrim($activity->max_score, '0'), '.') }}
                                    @else
                                        <span class="text-muted">Sin calificar</span>
                                    @endif
                                </td>
                                <td>{{ $grade?->source->label() ?? '—' }}</td>
                                <td>
                                    @if ($pending)
                                        <a href="{{ route('teacher.attempts.review', $pending) }}" class="link">Revisar intento</a>
                                    @elseif ($attempts->isNotEmpty())
                                        <a href="{{ route('teacher.attempts.review', $attempts->sortByDesc('score')->first()) }}" class="link">Ver intento</a>
                                    @endif

                                    @unless ($activity->isQuiz())
                                        <form method="POST" action="{{ route('teacher.activities.grades.store', $activity) }}" class="grade-inline">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="number" name="score" step="0.01" min="0" max="{{ $activity->max_score }}"
                                                   value="{{ $grade?->score }}" class="field__control" placeholder="Nota" aria-label="Nota">
                                            <button type="submit" class="btn btn--secondary btn--sm">Guardar</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
