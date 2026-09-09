@extends('layouts.app')

@section('title', 'Experiencias inmersivas')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Experiencias inmersivas</h1>
            <p class="page-header__subtitle">Catálogo de experiencias VR/AR/Unity y su vínculo con asignaturas.</p>
        </div>
        @can('create', App\Models\ImmersiveExperience::class)
            <x-ui.button :href="route('immersive.experiences.create')" variant="primary">Nueva experiencia</x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        @if ($experiences->isEmpty())
            <x-tables.empty-state message="Todavía no hay experiencias registradas." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Título</th><th>Proveedor</th><th>Asignaturas</th><th>Sesiones</th><th>Estado</th><th class="table__actions-col">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($experiences as $experience)
                            <tr>
                                <td><a href="{{ route('immersive.experiences.show', $experience) }}" class="link">{{ $experience->title }}</a></td>
                                <td>{{ $experience->provider->label() }}</td>
                                <td>{{ $experience->subjects_count }}</td>
                                <td>{{ $experience->sessions_count }}</td>
                                <td><x-ui.badge :color="$experience->status->badgeColor()">{{ $experience->status->label() }}</x-ui.badge></td>
                                <td class="table__actions">
                                    <a href="{{ route('immersive.experiences.edit', $experience) }}" class="link">Editar</a>
                                    <form method="POST" action="{{ route('immersive.experiences.destroy', $experience) }}"
                                          data-confirm="¿Eliminar «{{ $experience->title }}» y sus sesiones?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="link link--danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="u-mt-4">{{ $experiences->links() }}</div>
        @endif
    </x-ui.card>
@endsection
