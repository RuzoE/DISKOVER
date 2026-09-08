@extends('layouts.app')

@section('title', 'Analítica · '.$course->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Analítica de {{ $course->name }}</h1>
            <p class="page-header__subtitle"><code>{{ $course->code }}</code></p>
        </div>
        <x-ui.button :href="route('coordinator.courses.progress', $course)" variant="ghost">Ver progreso</x-ui.button>
    </div>

    <div class="dashboard-grid">
        <x-dashboard.stat-card icon="chart" tone="blue" label="Promedio del curso"
                               :value="$report['course_average'] !== null ? $report['course_average'].'%' : '—'" />
        <x-dashboard.stat-card icon="users" tone="neutral" label="Estudiantes" :value="$report['students_count']" />
        <x-dashboard.stat-card icon="book" tone="red" label="En riesgo" :value="count($report['at_risk'])" />
    </div>

    <x-ui.card title="Evolución del desempeño del grupo">
        <x-chart.line :points="$report['evolution']" />
    </x-ui.card>

    <div class="dashboard-cols">
        <x-ui.card title="Promedio por asignatura">
            <x-chart.bars :series="$report['by_subject']" />
        </x-ui.card>

        <x-ui.card title="Distribución de promedios">
            <x-chart.bars :series="$report['distribution']" :max="max(1, collect($report['distribution'])->max('value'))" unit="" />
        </x-ui.card>
    </div>

    <x-ui.card title="Estudiantes en riesgo">
        @if ($report['at_risk']->isEmpty())
            <x-tables.empty-state message="Ningún estudiante por debajo del umbral de riesgo." />
        @else
            <ul class="panel-list">
                @foreach ($report['at_risk'] as $r)
                    <li class="panel-list__item">
                        <span>{{ $r['name'] }}</span>
                        <span class="badge badge--red">{{ $r['average'] }}%</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
@endsection
