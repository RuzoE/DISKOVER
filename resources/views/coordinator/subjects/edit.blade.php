@extends('layouts.app')

@section('title', 'Editar asignatura')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Editar asignatura</h1>
            <p class="page-header__subtitle">
                <code>{{ $subject->code }}</code> · Curso: {{ $subject->course->name }}
            </p>
        </div>
        <x-ui.button :href="route('coordinator.subjects.show', $subject)" variant="ghost">Ver detalle</x-ui.button>
    </div>

    <x-ui.card>
        <x-academic.subject-form
            :action="route('coordinator.subjects.update', $subject)"
            method="PUT"
            :course="$subject->course"
            :subject="$subject"
            :teachers="$teachers"
            :statuses="$statuses"
        />
    </x-ui.card>
@endsection
