@extends('layouts.app')

@section('title', 'Editar curso')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Editar curso</h1>
            <p class="page-header__subtitle"><code>{{ $course->code }}</code></p>
        </div>
        <x-ui.button :href="route('coordinator.courses.show', $course)" variant="ghost">Ver detalle</x-ui.button>
    </div>

    <x-ui.card>
        <x-academic.course-form :action="route('coordinator.courses.update', $course)" method="PUT"
                                :course="$course" :statuses="$statuses" />
    </x-ui.card>
@endsection
