@extends('layouts.app')

@section('title', $experience->title)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $experience->title }}</h1>
            <p class="page-header__subtitle">
                <code>{{ $experience->slug }}</code> ·
                <x-ui.badge :color="$experience->status->badgeColor()">{{ $experience->status->label() }}</x-ui.badge>
                {{ $experience->provider->label() }}
            </p>
        </div>
        <x-ui.button :href="route('immersive.experiences.edit', $experience)" variant="primary">Editar</x-ui.button>
    </div>

    <x-ui.card title="Configuración">
        <dl class="detail-list">
            <div class="detail-list__row"><dt>Descripción</dt><dd>{{ $experience->description ?? '—' }}</dd></div>
            <div class="detail-list__row"><dt>URL de lanzamiento</dt><dd>{{ $experience->launch_url ?? '— (simulador)' }}</dd></div>
            <div class="detail-list__row"><dt>Puntuación máxima</dt><dd>{{ rtrim(rtrim($experience->max_score, '0'), '.') }}</dd></div>
            <div class="detail-list__row"><dt>Actividad vinculada</dt>
                <dd>{{ $experience->activity ? $experience->activity->title.' ('.$experience->activity->subject->name.')' : '— Sin vincular' }}</dd>
            </div>
            <div class="detail-list__row"><dt>Asignaturas</dt>
                <dd>
                    @forelse ($experience->subjects as $subject)
                        <x-ui.badge color="blue">{{ $subject->name }}@if ($subject->pivot->is_required) *@endif</x-ui.badge>
                    @empty
                        <span class="text-muted">Ninguna</span>
                    @endforelse
                </dd>
            </div>
            @if ($experience->config)
                <div class="detail-list__row"><dt>Config (JSON)</dt>
                    <dd><pre class="answer-box">{{ json_encode($experience->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></dd>
                </div>
            @endif
        </dl>
    </x-ui.card>

    <x-ui.card title="API para el cliente (Unity)">
        <p class="text-muted">El cliente inmersivo usa el <code>launch_token</code> de cada sesión:</p>
        <ul class="content-list">
            <li class="content-list__item"><code>GET /api/v1/immersive/sessions/{token}</code><span class="text-muted">configuración + estudiante</span></li>
            <li class="content-list__item"><code>POST /api/v1/immersive/sessions/{token}/complete</code><span class="text-muted">{ score, payload }</span></li>
            <li class="content-list__item"><code>POST /api/v1/immersive/sessions/{token}/abandon</code><span class="text-muted">cierre sin resultado</span></li>
        </ul>
    </x-ui.card>

    <x-ui.card title="Sesiones recientes">
        @if ($sessions->isEmpty())
            <x-tables.empty-state message="Sin sesiones todavía." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Estudiante</th><th>Estado</th><th>Puntuación</th><th>Inicio</th><th>Fin</th></tr></thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td>{{ $session->student->name }}</td>
                                <td><x-ui.badge :color="$session->status->badgeColor()">{{ $session->status->label() }}</x-ui.badge></td>
                                <td>{{ $session->score !== null ? rtrim(rtrim($session->score, '0'), '.').' / '.rtrim(rtrim($session->max_score, '0'), '.') : '—' }}</td>
                                <td>{{ $session->started_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $session->ended_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
