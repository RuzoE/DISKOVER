@extends('layouts.app')

@section('title', $subject->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $subject->name }}</h1>
            <p class="page-header__subtitle">
                <a href="{{ route('student.courses.show', $subject->course) }}" class="link">{{ $subject->course->name }}</a>
                · {{ $subject->teacher?->name ?? 'Docente por asignar' }}
            </p>
        </div>
    </div>

    @if ($subject->description)
        <x-ui.card title="Descripción">
            <p>{{ $subject->description }}</p>
        </x-ui.card>
    @endif

    <x-ui.card title="Actividades y evaluaciones">
        @if ($activities->isEmpty())
            <x-tables.empty-state message="No hay actividades publicadas todavía." />
        @else
            <ul class="content-list">
                @foreach ($activities as $activity)
                    <li class="content-list__item">
                        <a href="{{ route('student.activities.show', $activity) }}" class="link content-list__title">{{ $activity->title }}</a>
                        <x-ui.badge :color="$activity->isQuiz() ? 'blue' : 'neutral'">{{ $activity->type->label() }}</x-ui.badge>
                        @if ($activity->due_at)
                            <span class="text-muted">Entrega {{ $activity->due_at->format('d/m/Y') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <x-ui.card title="Contenidos">
        @if ($contents->isEmpty())
            <x-tables.empty-state message="El docente todavía no ha publicado contenidos." />
        @else
            <div class="content-blocks">
                @foreach ($contents as $content)
                    <article class="content-block">
                        <header class="content-block__header">
                            <h3 class="content-block__title">{{ $content->title }}</h3>
                            <x-ui.badge>{{ $content->type->label() }}</x-ui.badge>
                        </header>

                        @if ($content->type === App\Enums\ContentType::Text)
                            <div class="content-block__body">{!! nl2br(e($content->body)) !!}</div>
                        @else
                            <p><a href="{{ $content->url }}" class="link" target="_blank" rel="noopener">Abrir recurso ↗</a></p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </x-ui.card>
@endsection
