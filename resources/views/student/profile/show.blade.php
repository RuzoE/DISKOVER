@extends('layouts.app')

@section('title', 'Mi progreso')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Mi perfil académico</h1>
        <p class="page-header__subtitle">Resumen de tu avance y tu historial de aprendizaje.</p>
    </div>

    <div class="stat-grid">
        <x-ui.card>
            <p class="stat__label">Progreso global</p>
            <p class="stat__value">{{ rtrim(rtrim(number_format($profile['overall_percentage'], 1), '0'), '.') }}%</p>
        </x-ui.card>
        <x-ui.card>
            <p class="stat__label">Promedio general</p>
            <p class="stat__value">{{ $profile['overall_average'] !== null ? $profile['overall_average'].'%' : '—' }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="stat__label">Actividades calificadas</p>
            <p class="stat__value">{{ $profile['activities_graded'] }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="stat__label">Pendientes / vencidas</p>
            <p class="stat__value">{{ $profile['pending'] }} / {{ $profile['overdue'] }}</p>
        </x-ui.card>
    </div>

    <x-ui.card title="Cursos ({{ $profile['courses_count'] }})">
        @if (empty($profile['courses']))
            <x-tables.empty-state message="No estás inscrito en ningún curso." />
        @else
            <div class="progress-list">
                @foreach ($profile['courses'] as $row)
                    <div class="progress-list__item">
                        <div class="progress-list__head">
                            <a href="{{ route('student.courses.progress', $row['course']) }}" class="link"><strong>{{ $row['course']->name }}</strong></a>
                            <code>{{ $row['course']->code }}</code>
                        </div>
                        <x-analytics.progress-summary :progress="$row['progress']" />
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Historial de aprendizaje">
        <x-analytics.timeline :events="$history" />
    </x-ui.card>
@endsection
