@extends('layouts.app')

@section('title', $course->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $course->name }}</h1>
            <p class="page-header__subtitle">
                <code>{{ $course->code }}</code>
                <x-ui.badge :color="$course->status->badgeColor()">{{ $course->status->label() }}</x-ui.badge>
            </p>
        </div>
        <div class="u-flex u-gap-3">
            <x-ui.button :href="route('coordinator.courses.enrollments.index', $course)" variant="secondary">Inscripciones</x-ui.button>
            <x-ui.button :href="route('coordinator.courses.edit', $course)" variant="primary">Editar</x-ui.button>
        </div>
    </div>

    <x-ui.card title="Información">
        <dl class="detail-list">
            <div class="detail-list__row"><dt>Descripción</dt><dd>{{ $course->description ?? '—' }}</dd></div>
            <div class="detail-list__row"><dt>Periodo</dt>
                <dd>{{ $course->starts_on?->format('d/m/Y') ?? '—' }} → {{ $course->ends_on?->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div class="detail-list__row"><dt>Estudiantes inscritos</dt><dd>{{ $course->enrollments->count() }}</dd></div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Asignaturas">
        <x-slot:actions>
            @can('create', App\Models\Subject::class)
                <x-ui.button :href="route('coordinator.courses.subjects.create', $course)" variant="primary" class="btn--sm">Nueva asignatura</x-ui.button>
            @endcan
        </x-slot:actions>

        @if ($course->subjects->isEmpty())
            <x-tables.empty-state message="Este curso todavía no tiene asignaturas." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>#</th><th>Código</th><th>Nombre</th><th>Docente</th><th>Estado</th><th class="table__actions-col">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($course->subjects as $subject)
                            <tr>
                                <td>{{ $subject->position }}</td>
                                <td><code>{{ $subject->code }}</code></td>
                                <td><a href="{{ route('coordinator.subjects.show', $subject) }}" class="link">{{ $subject->name }}</a></td>
                                <td>{{ $subject->teacher?->name ?? '—' }}</td>
                                <td><x-ui.badge :color="$subject->status->badgeColor()">{{ $subject->status->label() }}</x-ui.badge></td>
                                <td class="table__actions">
                                    <a href="{{ route('coordinator.subjects.edit', $subject) }}" class="link">Editar</a>
                                    <form method="POST" action="{{ route('coordinator.subjects.destroy', $subject) }}"
                                          data-confirm="¿Eliminar la asignatura {{ $subject->name }}?">
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
