@extends('layouts.app')

@section('title', 'Nueva experiencia inmersiva')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Nueva experiencia inmersiva</h1>
    </div>

    <x-ui.card>
        <x-immersive.experience-form
            :action="route('immersive.experiences.store')"
            method="POST"
            :providers="$providers"
            :statuses="$statuses"
            :subjects="$subjects"
            :activity-options="$activityOptions"
        />
    </x-ui.card>
@endsection
