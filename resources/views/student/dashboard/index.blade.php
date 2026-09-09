@extends('layouts.app')

@section('title', 'Panel')

@php $p = $data['profile']; @endphp

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Hola, {{ $user->name }}</h1>
        <p class="page-header__subtitle">Este es el resumen de tu aprendizaje.</p>
    </div>

    <div class="dashboard-grid">
        <x-dashboard.stat-card icon="cap" tone="blue" label="Cursos" :value="$p['courses_count']" />
        <x-dashboard.stat-card icon="chart" tone="green" label="Progreso global"
                               :value="rtrim(rtrim(number_format($p['overall_percentage'], 1), '0'), '.').'%'" />
        <x-dashboard.stat-card icon="clipboard" tone="neutral" label="Promedio general"
                               :value="$p['overall_average'] !== null ? $p['overall_average'].'%' : '—'" />
        <x-dashboard.stat-card icon="book" tone="amber" label="Pendientes / vencidas"
                               :value="$p['pending'].' / '.$p['overdue']" />
    </div>

    <div class="dashboard-cols">
        <x-ui.card title="Próximas entregas">
            <x-slot:actions>
                <x-ui.button :href="route('student.courses.index')" variant="ghost" class="btn--sm">Mis cursos</x-ui.button>
            </x-slot:actions>

            @if ($data['upcoming']->isEmpty())
                <x-tables.empty-state message="No tienes actividades pendientes. ¡Buen trabajo!" />
            @else
                <ul class="panel-list">
                    @foreach ($data['upcoming'] as $activity)
                        <li class="panel-list__item">
                            <a href="{{ route('student.activities.show', $activity) }}" class="link">{{ $activity->title }}</a>
                            <span class="panel-list__meta">
                                {{ $activity->subject->name }} ·
                                {{ $activity->due_at ? $activity->due_at->format('d/m/Y') : 'sin fecha' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Progreso por curso">
            @if (empty($p['courses']))
                <x-tables.empty-state message="Aún no estás inscrito en ningún curso." />
            @else
                <div class="progress-list">
                    @foreach ($p['courses'] as $row)
                        <div>
                            <div class="progress-list__head">
                                <a href="{{ route('student.courses.progress', $row['course']) }}" class="link"><strong>{{ $row['course']->name }}</strong></a>
                            </div>
                            <x-ui.progress :value="$row['progress']['percentage']" />
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Recomendaciones para ti">
        <x-slot:actions>
            <x-ui.button :href="route('student.recommendations.index')" variant="ghost" class="btn--sm">
                Ver todas ({{ $data['recommendation_stats']['open'] }})
            </x-ui.button>
        </x-slot:actions>

        @if ($data['recommendations']->isEmpty())
            <x-tables.empty-state message="Sin recomendaciones abiertas ahora mismo." />
        @else
            <div class="rec-list">
                @foreach ($data['recommendations'] as $recommendation)
                    <x-recommendations.card :recommendation="$recommendation" />
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Actividad reciente">
        <x-slot:actions>
            <x-ui.button :href="route('student.profile.show')" variant="ghost" class="btn--sm">Ver todo</x-ui.button>
        </x-slot:actions>
        <x-analytics.timeline :events="$data['history']" />
    </x-ui.card>
@endsection
