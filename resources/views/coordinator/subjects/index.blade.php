@extends('layouts.app')

@section('title', 'Asignaturas · '.$course->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Asignaturas</h1>
            <p class="page-header__subtitle">
                Curso <a href="{{ route('coordinator.courses.show', $course) }}" class="link">{{ $course->name }}</a>
            </p>
        </div>
        @can('create', App\Models\Subject::class)
            <x-ui.button :href="route('coordinator.courses.subjects.create', $course)" variant="primary">Nueva asignatura</x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        @if ($subjects->isEmpty())
            <x-tables.empty-state message="Este curso no tiene asignaturas." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>#</th><th>Código</th><th>Nombre</th><th>Docente</th><th>Contenidos</th><th>Estado</th><th class="table__actions-col">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $subject)
                            <tr>
                                <td>{{ $subject->position }}</td>
                                <td><code>{{ $subject->code }}</code></td>
                                <td><a href="{{ route('coordinator.subjects.show', $subject) }}" class="link">{{ $subject->name }}</a></td>
                                <td>{{ $subject->teacher?->name ?? '—' }}</td>
                                <td>{{ $subject->contents_count }}</td>
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
