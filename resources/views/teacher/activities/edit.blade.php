@extends('layouts.app')

@section('title', 'Editar actividad')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Editar actividad</h1>
            <p class="page-header__subtitle">{{ $activity->subject->name }} · {{ $activity->subject->course->name }}</p>
        </div>
        <x-ui.button :href="route('teacher.activities.show', $activity)" variant="ghost">Ver detalle</x-ui.button>
    </div>

    <x-ui.card>
        <x-academic.activity-form
            :action="route('teacher.activities.update', $activity)"
            method="PUT"
            :subject="$activity->subject"
            :activity="$activity"
        />
    </x-ui.card>
@endsection
