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
