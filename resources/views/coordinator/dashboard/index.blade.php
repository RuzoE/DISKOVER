@extends('layouts.app')

@section('title', 'Panel')

@php $s = $data['stats']; @endphp

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Panel de coordinación</h1>
        <p class="page-header__subtitle">Estado general de cursos, asignaturas e inscripciones.</p>
    </div>

    <div class="dashboard-grid">
        <x-dashboard.stat-card icon="book" tone="blue" label="Cursos"
                               :value="$s['courses']" :hint="$s['courses_active'].' activos'" />
        <x-dashboard.stat-card icon="stack" tone="neutral" label="Asignaturas" :value="$s['subjects']" />
        <x-dashboard.stat-card icon="users" tone="green" label="Estudiantes inscritos" :value="$s['students']" />
        <x-dashboard.stat-card icon="cap" tone="amber" label="Docentes con asignatura" :value="$s['teachers']" />
    </div>

    <x-ui.card title="Cursos activos">
        <x-slot:actions>
            <x-ui.button :href="route('coordinator.courses.index')" variant="ghost" class="btn--sm">Gestionar</x-ui.button>
        </x-slot:actions>

        @if ($data['courses']->isEmpty())
            <x-tables.empty-state message="No hay cursos activos." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Código</th><th>Curso</th><th>Asignaturas</th><th>Inscritos</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($data['courses'] as $course)
                            <tr>
                                <td><code>{{ $course->code }}</code></td>
                                <td>{{ $course->name }}</td>
                                <td>{{ $course->subjects_count }}</td>
                                <td>{{ $course->enrollments_count }}</td>
                                <td class="table__actions">
                                    <a href="{{ route('coordinator.courses.progress', $course) }}" class="link">Progreso</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <div class="dashboard-cols">
        <x-ui.card title="Asignaturas sin docente">
            @if ($data['subjects_without_teacher']->isEmpty())
                <x-tables.empty-state message="Todas las asignaturas tienen docente asignado." />
            @else
                <ul class="panel-list">
                    @foreach ($data['subjects_without_teacher'] as $subject)
                        <li class="panel-list__item">
                            <a href="{{ route('coordinator.subjects.edit', $subject) }}" class="link">{{ $subject->name }}</a>
                            <span class="panel-list__meta">{{ $subject->course->name }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Actividad reciente">
            <x-analytics.timeline :events="$data['history']" />
        </x-ui.card>
    </div>
@endsection
