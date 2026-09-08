@extends('layouts.app')

@section('title', 'Inscripciones · '.$course->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Inscripciones</h1>
            <p class="page-header__subtitle">
                Curso <a href="{{ route('coordinator.courses.show', $course) }}" class="link">{{ $course->name }}</a>
            </p>
        </div>
    </div>

    <x-ui.card title="Inscribir estudiante">
        @if (empty($availableStudents))
            <p class="text-muted">No hay estudiantes disponibles para inscribir.</p>
        @else
            <form method="POST" action="{{ route('coordinator.courses.enrollments.store', $course) }}" class="filters">
                @csrf
                <x-ui.select name="student_id" :options="$availableStudents" placeholder="— Selecciona un estudiante —" required aria-label="Estudiante" />
                <x-ui.button type="submit" variant="primary">Inscribir</x-ui.button>
            </form>
        @endif
    </x-ui.card>

    <x-ui.card title="Estudiantes inscritos ({{ $enrollments->count() }})">
        @if ($enrollments->isEmpty())
            <x-tables.empty-state message="Nadie inscrito todavía." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Estudiante</th><th>Correo</th><th>Inscrito</th><th>Estado</th><th class="table__actions-col">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($enrollments as $enrollment)
                            <tr>
                                <td>{{ $enrollment->student->name }}</td>
                                <td>{{ $enrollment->student->email }}</td>
                                <td>{{ $enrollment->enrolled_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('coordinator.enrollments.update', $enrollment) }}" class="u-flex u-gap-3 u-items-center">
                                        @csrf @method('PUT')
                                        <select name="status" class="field__control" onchange="this.form.submit()" aria-label="Cambiar estado">
                                            @foreach ($statuses as $value => $label)
                                                <option value="{{ $value }}" @selected($enrollment->status->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="table__actions">
                                    <form method="POST" action="{{ route('coordinator.enrollments.destroy', $enrollment) }}"
                                          data-confirm="¿Eliminar la inscripción de {{ $enrollment->student->name }}?">
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
