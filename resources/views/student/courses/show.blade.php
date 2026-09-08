@extends('layouts.app')

@section('title', $course->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $course->name }}</h1>
            <p class="page-header__subtitle"><code>{{ $course->code }}</code></p>
        </div>
        <x-ui.button :href="route('student.courses.index')" variant="ghost">Mis cursos</x-ui.button>
    </div>

    <x-ui.card title="Sobre el curso">
        <p>{{ $course->description ?? 'Sin descripción.' }}</p>
    </x-ui.card>

    <x-ui.card title="Asignaturas">
        @if ($course->subjects->isEmpty())
            <x-tables.empty-state message="Este curso no tiene asignaturas activas." />
        @else
            <ul class="content-list">
                @foreach ($course->subjects as $subject)
                    <li class="content-list__item">
                        <a href="{{ route('student.subjects.show', $subject) }}" class="link content-list__title">{{ $subject->name }}</a>
                        <span class="text-muted">{{ $subject->teacher?->name ?? 'Docente por asignar' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
@endsection
