@extends('layouts.app')

@section('title', 'Editar experiencia')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Editar experiencia</h1>
            <p class="page-header__subtitle"><code>{{ $experience->slug }}</code></p>
        </div>
        <x-ui.button :href="route('immersive.experiences.show', $experience)" variant="ghost">Ver detalle</x-ui.button>
    </div>

    <x-ui.card>
        <x-immersive.experience-form
            :action="route('immersive.experiences.update', $experience)"
            method="PUT"
            :experience="$experience"
            :providers="$providers"
            :statuses="$statuses"
            :subjects="$subjects"
            :activity-options="$activityOptions"
        />
    </x-ui.card>
@endsection
