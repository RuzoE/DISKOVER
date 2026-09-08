@extends('layouts.app')

@section('title', 'Mis cursos')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Mis cursos</h1>
        <p class="page-header__subtitle">Cursos en los que estás inscrito.</p>
    </div>

    @if ($courses->isEmpty())
        <x-ui.card>
            <x-tables.empty-state message="Todavía no estás inscrito en ningún curso." />
        </x-ui.card>
    @else
        <div class="card-grid">
            @foreach ($courses as $course)
                <x-ui.card :title="$course->name">
                    <p class="text-muted"><code>{{ $course->code }}</code></p>
                    <p class="u-mt-2">{{ Str::limit($course->description, 120) ?: 'Sin descripción.' }}</p>
                    <p class="u-mt-2 text-muted">{{ $course->subjects_count }} asignatura(s) activa(s)</p>
                    <div class="u-mt-4">
                        <x-ui.button :href="route('student.courses.show', $course)" variant="primary" class="btn--sm">Entrar</x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
@endsection
