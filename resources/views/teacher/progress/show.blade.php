@extends('layouts.app')

@section('title', 'Progreso · '.$subject->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Progreso de la asignatura</h1>
            <p class="page-header__subtitle">
                <a href="{{ route('teacher.subjects.show', $subject) }}" class="link">{{ $subject->name }}</a>
                · {{ $subject->course->name }}
                @if ($overview['average'] !== null) · Promedio del grupo: <strong>{{ $overview['average'] }}%</strong> @endif
            </p>
        </div>
    </div>

    <x-ui.card>
        @if (empty($overview['rows']))
            <x-tables.empty-state message="No hay estudiantes inscritos en el curso." />
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
