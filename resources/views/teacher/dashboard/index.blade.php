@extends('layouts.app')

@section('title', 'Panel')

@php $s = $data['stats']; @endphp

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Hola, {{ $user->name }}</h1>
        <p class="page-header__subtitle">Resumen de tus asignaturas y tareas de calificación.</p>
    </div>

    <div class="dashboard-grid">
        <x-dashboard.stat-card icon="stack" tone="blue" label="Asignaturas" :value="$s['subjects']" />
        <x-dashboard.stat-card icon="users" tone="neutral" label="Estudiantes" :value="$s['students']" />
        <x-dashboard.stat-card icon="clipboard" tone="green" label="Actividades publicadas" :value="$s['activities']" />
        <x-dashboard.stat-card icon="chart" tone="amber" label="Intentos por revisar" :value="$s['pending_review']" />
    </div>

    <div class="dashboard-cols">
        <x-ui.card title="Pendiente de calificar">
            @if ($data['pending_review']->isEmpty())
                <x-tables.empty-state message="No hay intentos a la espera de revisión." />
            @else
                <ul class="panel-list">
                    @foreach ($data['pending_review'] as $attempt)
                        <li class="panel-list__item">
                            <a href="{{ route('teacher.attempts.review', $attempt) }}" class="link">
                                {{ $attempt->student->name }} · {{ $attempt->evaluation->activity->title }}
                            </a>
                            <span class="panel-list__meta">enviado {{ $attempt->submitted_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Mis asignaturas">
            <x-slot:actions>
                <x-ui.button :href="route('teacher.subjects.index')" variant="ghost" class="btn--sm">Ver todas</x-ui.button>
            </x-slot:actions>

            @if ($data['subjects']->isEmpty())
                <x-tables.empty-state message="No tienes asignaturas asignadas." />
            @else
                <ul class="panel-list">
                    @foreach ($data['subjects'] as $subject)
                        <li class="panel-list__item">
                            <a href="{{ route('teacher.subjects.show', $subject) }}" class="link">{{ $subject->name }}</a>
                            <span class="panel-list__meta">
                                {{ $subject->course->name }} ·
                                {{ $subject->activities_count }} act. · {{ $subject->contents_count }} cont.
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Actividad reciente en tus asignaturas">
        <x-analytics.timeline :events="$data['history']" />
    </x-ui.card>
@endsection
