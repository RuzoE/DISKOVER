@extends('layouts.app')

@section('title', 'Progreso · '.$course->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Progreso del curso</h1>
            <p class="page-header__subtitle">
                <a href="{{ route('coordinator.courses.show', $course) }}" class="link">{{ $course->name }}</a>
                · <code>{{ $course->code }}</code>
            </p>
        </div>
    </div>

    <div class="stat-grid">
        <x-ui.card>
            <p class="stat__label">Avance medio</p>
            <p class="stat__value">{{ rtrim(rtrim(number_format($overview['average_progress'], 1), '0'), '.') }}%</p>
        </x-ui.card>
        <x-ui.card>
            <p class="stat__label">Promedio de calificaciones</p>
            <p class="stat__value">{{ $overview['average'] !== null ? $overview['average'].'%' : '—' }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="stat__label">Estudiantes</p>
            <p class="stat__value">{{ count($overview['rows']) }}</p>
        </x-ui.card>
    </div>

    <x-ui.card>
        @if (empty($overview['rows']))
            <x-tables.empty-state message="El curso no tiene estudiantes inscritos." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Estudiante</th><th>Progreso</th><th>Contenidos</th><th>Actividades</th><th>Promedio</th><th>Pend. / Venc.</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($overview['rows'] as $row)
                            @php $p = $row['progress']; @endphp
                            <tr>
                                <td>{{ $row['student']->name }}</td>
                                <td style="min-width: 160px;"><x-ui.progress :value="$p['percentage']" /></td>
                                <td>{{ $p['contents_done'] }}/{{ $p['contents_total'] }}</td>
                                <td>{{ $p['activities_graded'] }}/{{ $p['activities_total'] }}</td>
                                <td>{{ $p['average'] !== null ? $p['average'].'%' : '—' }}</td>
                                <td>{{ $p['pending'] }} / <span class="{{ $p['overdue'] ? 'link--danger' : '' }}">{{ $p['overdue'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
