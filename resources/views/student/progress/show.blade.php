@extends('layouts.app')

@section('title', 'Progreso · '.$course->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Progreso en {{ $course->name }}</h1>
            <p class="page-header__subtitle"><code>{{ $course->code }}</code></p>
        </div>
        <x-ui.button :href="route('student.courses.show', $course)" variant="ghost">Ver curso</x-ui.button>
    </div>

    <x-ui.card title="Resumen del curso">
        <x-analytics.progress-summary :progress="$progress" label="Avance total" />
    </x-ui.card>

    <x-ui.card title="Por asignatura">
        @if (empty($progress['subjects']))
            <x-tables.empty-state message="Este curso no tiene asignaturas activas." />
        @else
            <div class="progress-list">
                @foreach ($progress['subjects'] as $row)
                    <div class="progress-list__item">
                        <div class="progress-list__head">
                            <a href="{{ route('student.subjects.show', $row['subject']) }}" class="link"><strong>{{ $row['subject']->name }}</strong></a>
                        </div>
                        <x-analytics.progress-summary :progress="$row['progress']" />
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>
@endsection
