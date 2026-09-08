@extends('layouts.app')

@section('title', $subject->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $subject->name }}</h1>
            <p class="page-header__subtitle">
                <code>{{ $subject->code }}</code> · {{ $subject->course->name }} ·
                <x-ui.badge :color="$subject->status->badgeColor()">{{ $subject->status->label() }}</x-ui.badge>
            </p>
        </div>
        <div class="u-flex u-gap-3">
            <x-ui.button :href="route('teacher.subjects.activities.index', $subject)" variant="secondary">Actividades</x-ui.button>
            <x-ui.button :href="route('teacher.subjects.contents.index', $subject)" variant="primary">Contenidos</x-ui.button>
        </div>
    </div>

    <x-ui.card title="Descripción">
        <p>{{ $subject->description ?? 'Sin descripción.' }}</p>
    </x-ui.card>

    <x-ui.card title="Contenidos ({{ $subject->contents->count() }})">
        @if ($subject->contents->isEmpty())
            <x-tables.empty-state message="Aún no has añadido contenidos." />
        @else
            <ul class="content-list">
                @foreach ($subject->contents as $content)
                    <li class="content-list__item">
                        <span class="content-list__title">{{ $content->title }}</span>
                        <x-ui.badge>{{ $content->type->label() }}</x-ui.badge>
                        <x-ui.badge :color="$content->is_published ? 'green' : 'amber'">
                            {{ $content->is_published ? 'Publicado' : 'Borrador' }}
                        </x-ui.badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
@endsection
