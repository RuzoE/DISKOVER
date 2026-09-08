@extends('layouts.app')

@section('title', 'Editar contenido')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Editar contenido</h1>
            <p class="page-header__subtitle">{{ $subject->name }} · {{ $subject->course->name }}</p>
        </div>
        <x-ui.button :href="route('teacher.subjects.contents.index', $subject)" variant="ghost">Volver</x-ui.button>
    </div>

    <x-ui.card>
        <x-academic.content-form
            :action="route('teacher.contents.update', $content)"
            method="PUT"
            :subject="$subject"
            :content="$content"
            :types="$types"
        />
    </x-ui.card>
@endsection
