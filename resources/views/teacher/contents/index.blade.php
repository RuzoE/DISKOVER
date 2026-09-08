@extends('layouts.app')

@section('title', 'Contenidos · '.$subject->name)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Contenidos</h1>
            <p class="page-header__subtitle">
                <a href="{{ route('teacher.subjects.show', $subject) }}" class="link">{{ $subject->name }}</a>
                · {{ $subject->course->name }}
            </p>
        </div>
        @can('create', [App\Models\Content::class, $subject])
            <x-ui.button :href="route('teacher.subjects.contents.create', $subject)" variant="primary">Nuevo contenido</x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        @if ($contents->isEmpty())
            <x-tables.empty-state message="Sin contenidos todavía." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>#</th><th>Título</th><th>Tipo</th><th>Recurso</th><th>Estado</th><th class="table__actions-col">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($contents as $content)
                            <tr>
                                <td>{{ $content->position }}</td>
                                <td>{{ $content->title }}</td>
                                <td><x-ui.badge>{{ $content->type->label() }}</x-ui.badge></td>
                                <td>
                                    @if ($content->url)
                                        <a href="{{ $content->url }}" class="link" target="_blank" rel="noopener">Abrir enlace</a>
                                    @else
                                        <span class="text-muted">Texto</span>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.badge :color="$content->is_published ? 'green' : 'amber'">
                                        {{ $content->is_published ? 'Publicado' : 'Borrador' }}
                                    </x-ui.badge>
                                </td>
                                <td class="table__actions">
                                    <a href="{{ route('teacher.contents.edit', $content) }}" class="link">Editar</a>
                                    <form method="POST" action="{{ route('teacher.contents.destroy', $content) }}"
                                          data-confirm="¿Eliminar el contenido «{{ $content->title }}»?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="link link--danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
