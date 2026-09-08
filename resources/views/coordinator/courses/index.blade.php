@extends('layouts.app')

@section('title', 'Cursos')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Cursos</h1>
            <p class="page-header__subtitle">Estructura académica: cursos, asignaturas e inscripciones.</p>
        </div>
        @can('create', App\Models\Course::class)
            <x-ui.button :href="route('coordinator.courses.create')" variant="primary">Nuevo curso</x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        <form method="GET" action="{{ route('coordinator.courses.index') }}" class="filters">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="Buscar por nombre o código" class="field__control" aria-label="Buscar cursos">
            <select name="status" class="field__control" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
        </form>

        @if ($courses->isEmpty())
            <x-tables.empty-state message="No hay cursos que coincidan." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Asignaturas</th>
                            <th>Inscritos</th>
                            <th>Estado</th>
                            <th class="table__actions-col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                            <tr>
                                <td><code>{{ $course->code }}</code></td>
                                <td><a href="{{ route('coordinator.courses.show', $course) }}" class="link">{{ $course->name }}</a></td>
                                <td>{{ $course->subjects_count }}</td>
                                <td>{{ $course->enrollments_count }}</td>
                                <td><x-ui.badge :color="$course->status->badgeColor()">{{ $course->status->label() }}</x-ui.badge></td>
                                <td class="table__actions">
                                    <a href="{{ route('coordinator.courses.progress', $course) }}" class="link">Progreso</a>
                                    <a href="{{ route('coordinator.courses.edit', $course) }}" class="link">Editar</a>
                                    @can('delete', $course)
                                        <form method="POST" action="{{ route('coordinator.courses.destroy', $course) }}"
                                              data-confirm="¿Eliminar el curso {{ $course->name }}? Se eliminarán sus asignaturas, contenidos e inscripciones.">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="link link--danger">Eliminar</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="u-mt-4">{{ $courses->links() }}</div>
        @endif
    </x-ui.card>
@endsection
