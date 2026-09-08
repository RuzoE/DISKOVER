@extends('layouts.app')

@section('title', $subject->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $subject->name }}</h1>
            <p class="page-header__subtitle">
                <code>{{ $subject->code }}</code> ·
                Curso <a href="{{ route('coordinator.courses.show', $subject->course) }}" class="link">{{ $subject->course->name }}</a> ·
                <x-ui.badge :color="$subject->status->badgeColor()">{{ $subject->status->label() }}</x-ui.badge>
            </p>
        </div>
        <x-ui.button :href="route('coordinator.subjects.edit', $subject)" variant="primary">Editar</x-ui.button>
    </div>

    <x-ui.card title="Información">
        <dl class="detail-list">
            <div class="detail-list__row"><dt>Descripción</dt><dd>{{ $subject->description ?? '—' }}</dd></div>
            <div class="detail-list__row"><dt>Docente</dt><dd>{{ $subject->teacher?->name ?? 'Sin asignar' }}</dd></div>
            <div class="detail-list__row"><dt>Orden</dt><dd>{{ $subject->position }}</dd></div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Contenidos ({{ $subject->contents->count() }})">
        @if ($subject->contents->isEmpty())
            <x-tables.empty-state message="Sin contenidos. El docente asignado los gestiona desde su panel." />
        @else
            <ul class="content-list">
                @foreach ($subject->contents as $content)
                    <li class="content-list__item">
                        <span class="content-list__title">{{ $content->title }}</span>
                        <x-ui.badge>{{ $content->type->label() }}</x-ui.badge>
                        @if ($content->is_published)
                            <x-ui.badge color="green">Publicado</x-ui.badge>
                        @else
                            <x-ui.badge color="amber">Borrador</x-ui.badge>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
@endsection
