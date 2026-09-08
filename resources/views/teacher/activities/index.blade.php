@extends('layouts.app')

@section('title', 'Actividades · '.$subject->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Actividades y evaluaciones</h1>
            <p class="page-header__subtitle">
                <a href="{{ route('teacher.subjects.show', $subject) }}" class="link">{{ $subject->name }}</a>
                · {{ $subject->course->name }}
            </p>
        </div>
        <x-ui.button :href="route('teacher.subjects.activities.create', $subject)" variant="primary">Nueva actividad</x-ui.button>
    </div>

    <x-ui.card>
        @if ($activities->isEmpty())
            <x-tables.empty-state message="Todavía no has creado actividades." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>#</th><th>Título</th><th>Tipo</th><th>Máx.</th><th>Entrega</th><th>Calificados</th><th>Estado</th><th class="table__actions-col">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($activities as $activity)
                            <tr>
                                <td>{{ $activity->position }}</td>
                                <td><a href="{{ route('teacher.activities.show', $activity) }}" class="link">{{ $activity->title }}</a></td>
                                <td><x-ui.badge :color="$activity->isQuiz() ? 'blue' : 'neutral'">{{ $activity->type->label() }}</x-ui.badge></td>
                                <td>{{ rtrim(rtrim($activity->max_score, '0'), '.') }}</td>
                                <td>{{ $activity->due_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $activity->grades_count }}</td>
                                <td><x-ui.badge :color="$activity->is_published ? 'green' : 'amber'">{{ $activity->is_published ? 'Publicada' : 'Borrador' }}</x-ui.badge></td>
                                <td class="table__actions">
                                    <a href="{{ route('teacher.activities.gradebook', $activity) }}" class="link">Calificar</a>
                                    <a href="{{ route('teacher.activities.edit', $activity) }}" class="link">Editar</a>
                                    <form method="POST" action="{{ route('teacher.activities.destroy', $activity) }}"
                                          data-confirm="¿Eliminar «{{ $activity->title }}» y todos sus datos asociados?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="link link--danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
