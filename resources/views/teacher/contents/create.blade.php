@extends('layouts.app')

@section('title', 'Nuevo contenido')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Nuevo contenido</h1>
        <p class="page-header__subtitle">{{ $subject->name }} · {{ $subject->course->name }}</p>
    </div>

    <x-ui.card>
        <x-academic.content-form
            :action="route('teacher.subjects.contents.store', $subject)"
            method="POST"
            :subject="$subject"
            :types="$types"
        />
    </x-ui.card>
@endsection
