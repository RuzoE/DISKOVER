@extends('layouts.app')

@section('title', 'Analítica · '.$subject->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Analítica de {{ $subject->name }}</h1>
            <p class="page-header__subtitle">
                {{ $subject->course->name }}
                @if ($overview['average'] !== null) · Promedio del grupo: <strong>{{ $overview['average'] }}%</strong> @endif
            </p>
        </div>
        <x-ui.button :href="route('teacher.subjects.progress', $subject)" variant="ghost">Ver progreso</x-ui.button>
    </div>

    <div class="dashboard-cols">
        <x-ui.card title="Promedio por estudiante">
            <x-chart.bars :series="$studentSeries" />
        </x-ui.card>

        <x-ui.card title="Distribución de promedios">
            <x-chart.bars :series="$distribution" :max="max(1, collect($distribution)->max('value'))" unit="" />
        </x-ui.card>
    </div>

    <div class="dashboard-cols">
        <x-ui.card title="Estudiantes en riesgo">
            @if ($atRisk->isEmpty())
                <x-tables.empty-state message="Ningún estudiante por debajo del umbral de riesgo." />
            @else
                <ul class="panel-list">
                    @foreach ($atRisk as $r)
                        <li class="panel-list__item">
                            <span>{{ $r['name'] }}</span>
                            <span class="badge badge--red">{{ $r['average'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Preguntas más difíciles">
            @if ($difficultQuestions->isEmpty())
                <x-tables.empty-state message="Sin datos de evaluaciones o ninguna pregunta problemática." />
            @else
                <ul class="panel-list">
                    @foreach ($difficultQuestions as $q)
                        <li class="panel-list__item">
                            <span>{{ Str::limit($q['statement'], 80) }}
                                <span class="panel-list__meta">· {{ $q['activity'] }} · {{ $q['answered'] }} resp.</span>
                            </span>
                            <span class="badge badge--amber">{{ round($q['success_rate'] * 100) }}% acierto</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
@endsection
