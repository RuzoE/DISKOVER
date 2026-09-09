@extends('layouts.app')

@section('title', 'Experiencias inmersivas')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Experiencias inmersivas</h1>
        <p class="page-header__subtitle">Actividades VR/AR de tus asignaturas.</p>
    </div>

    <x-ui.card title="Disponibles">
        @if ($experiences->isEmpty())
            <x-tables.empty-state message="No hay experiencias inmersivas en tus asignaturas por ahora." />
        @else
            <div class="card-grid">
                @foreach ($experiences as $experience)
                    <div class="immersive-tile">
                        <h3 class="immersive-tile__title">{{ $experience->title }}</h3>
                        <p class="text-muted">{{ $experience->provider->label() }}</p>
                        <p class="u-mt-2">{{ Str::limit($experience->description, 140) ?: 'Sin descripción.' }}</p>
                        <p class="text-muted u-mt-2">{{ $experience->subjects->pluck('name')->join(', ') }}</p>
                        <form method="POST" action="{{ route('student.immersive.launch', $experience) }}" class="u-mt-4">
                            @csrf
                            <x-ui.button type="submit" variant="primary" class="btn--sm">Comenzar</x-ui.button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Tus sesiones">
        @if ($sessions->isEmpty())
            <x-tables.empty-state message="Aún no has iniciado ninguna experiencia." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Experiencia</th><th>Estado</th><th>Puntuación</th><th>Fecha</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td>{{ $session->experience->title }}</td>
                                <td><x-ui.badge :color="$session->status->badgeColor()">{{ $session->status->label() }}</x-ui.badge></td>
                                <td>
                                    @if ($session->percentage() !== null)
                                        {{ $session->percentage() }}%
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $session->started_at?->format('d/m/Y H:i') }}</td>
                                <td class="table__actions">
                                    <a href="{{ route('student.immersive.session', $session) }}" class="link">
                                        {{ $session->status->isOpen() ? 'Continuar' : 'Ver' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
