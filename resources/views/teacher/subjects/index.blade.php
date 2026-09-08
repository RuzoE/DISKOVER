@extends('layouts.app')

@section('title', 'Mis asignaturas')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Mis asignaturas</h1>
        <p class="page-header__subtitle">Asignaturas que impartes y sus contenidos.</p>
    </div>

    <x-ui.card>
        @if ($subjects->isEmpty())
            <x-tables.empty-state message="No tienes asignaturas asignadas. Contacta con coordinación." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Código</th><th>Asignatura</th><th>Curso</th><th>Contenidos</th><th>Estado</th><th class="table__actions-col">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $subject)
                            <tr>
                                <td><code>{{ $subject->code }}</code></td>
                                <td><a href="{{ route('teacher.subjects.show', $subject) }}" class="link">{{ $subject->name }}</a></td>
                                <td>{{ $subject->course->name }}</td>
                                <td>{{ $subject->contents_count }}</td>
                                <td><x-ui.badge :color="$subject->status->badgeColor()">{{ $subject->status->label() }}</x-ui.badge></td>
                                <td class="table__actions">
                                    <a href="{{ route('teacher.subjects.progress', $subject) }}" class="link">Progreso</a>
                                    <a href="{{ route('teacher.subjects.activities.index', $subject) }}" class="link">Actividades</a>
                                    <a href="{{ route('teacher.subjects.contents.index', $subject) }}" class="link">Contenidos</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
