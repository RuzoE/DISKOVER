@extends('layouts.app')

@section('title', 'Analítica de aprendizaje')

@php $threshold = rtrim(rtrim(number_format((float) config('dsle.analytics.pass_threshold'), 1), '0'), '.'); @endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Mi analítica de aprendizaje</h1>
            <p class="page-header__subtitle">Evolución, desempeño por asignatura y puntos a reforzar.</p>
        </div>
        <x-ui.button :href="route('student.profile.show')" variant="ghost">Volver al perfil</x-ui.button>
    </div>

    <div class="dashboard-grid">
        <x-dashboard.stat-card icon="chart" tone="blue" label="Promedio general"
                               :value="$report['overall_average'] !== null ? $report['overall_average'].'%' : '—'" />
        <x-dashboard.stat-card icon="clipboard" tone="neutral" label="Actividades calificadas" :value="$report['graded_count']" />
        <x-dashboard.stat-card icon="book" tone="amber" label="Asignaturas a reforzar" :value="count($report['weak_subjects'])" />
    </div>

    <x-ui.card title="Evolución del desempeño">
        <x-chart.line :points="$report['evolution']" />
    </x-ui.card>

    <div class="dashboard-cols">
        <x-ui.card title="Promedio por asignatura">
            <x-chart.bars :series="$report['by_subject']" />
        </x-ui.card>

        <x-ui.card title="Distribución de tus notas">
            <x-chart.bars :series="$report['distribution']" :max="max(1, collect($report['distribution'])->max('value'))" unit="" />
        </x-ui.card>
    </div>

    <div class="dashboard-cols">
        <x-ui.card :title="'Actividades por debajo del '.$threshold.'%'">
            @if ($report['weak_activities']->isEmpty())
                <x-tables.empty-state message="Ninguna actividad por debajo del umbral. ¡Bien!" />
            @else
                <ul class="panel-list">
                    @foreach ($report['weak_activities'] as $a)
                        <li class="panel-list__item">
                            <span>{{ $a['title'] }} <span class="panel-list__meta">· {{ $a['subject'] }}</span></span>
                            <span class="badge badge--red">{{ rtrim(rtrim(number_format($a['percent'], 1), '0'), '.') }}%</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Asignaturas a reforzar">
            @if ($report['weak_subjects']->isEmpty())
                <x-tables.empty-state message="Todas tus asignaturas superan el umbral." />
            @else
                <ul class="panel-list">
                    @foreach ($report['weak_subjects'] as $s)
                        <li class="panel-list__item">
                            <span>{{ $s['name'] }}</span>
                            <span class="badge badge--amber">{{ $s['average'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
@endsection
