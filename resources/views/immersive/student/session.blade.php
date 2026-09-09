@extends('layouts.app')

@section('title', $session->experience->title)

@php
    $experience = $session->experience;
    $completeUrl = url('/api/v1/immersive/sessions/'.$session->launch_token.'/complete');
    $launchWithToken = $experience->launch_url
        ? $experience->launch_url.(str_contains($experience->launch_url, '?') ? '&' : '?').'token='.$session->launch_token
        : null;
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $experience->title }}</h1>
            <p class="page-header__subtitle">
                <x-ui.badge :color="$session->status->badgeColor()">{{ $session->status->label() }}</x-ui.badge>
                {{ $experience->provider->label() }}
            </p>
        </div>
        <x-ui.button :href="route('student.immersive.index')" variant="ghost">Volver</x-ui.button>
    </div>

    @if ($session->status === App\Enums\ImmersiveSessionStatus::Completed)
        <x-ui.card title="Resultado">
            <p class="grade-big">
                {{ rtrim(rtrim($session->score, '0'), '.') }}
                <span>/ {{ rtrim(rtrim($session->max_score, '0'), '.') }}</span>
            </p>
            @if ($session->percentage() !== null)<p class="text-muted">{{ $session->percentage() }}%</p>@endif
            @if ($experience->activity_id)
                <p class="text-muted u-mt-4">Esta experiencia está vinculada a la actividad
                    «{{ $experience->activity?->title }}»: tu calificación se ha registrado automáticamente.</p>
            @endif
        </x-ui.card>
    @elseif ($session->isExpired())
        <x-ui.alert type="warning">La sesión ha caducado. Vuelve a la lista y comiénzala de nuevo.</x-ui.alert>
    @else
        <x-ui.card title="Experiencia">
            @if ($experience->description)
                <p class="u-mb-4">{{ $experience->description }}</p>
            @endif

            @if ($experience->provider->isEmbeddable() && $launchWithToken)
                <div class="immersive-frame">
                    <iframe src="{{ $launchWithToken }}" allow="xr-spatial-tracking; fullscreen; gyroscope; accelerometer"
                            title="{{ $experience->title }}"></iframe>
                </div>
                <p class="text-muted u-mt-2">Al terminar, el resultado se registra automáticamente.</p>
            @elseif ($launchWithToken)
                <p><a href="{{ $launchWithToken }}" class="btn btn--primary" target="_blank" rel="noopener">Abrir la experiencia ↗</a></p>
                <p class="text-muted u-mt-2">Abre la experiencia en Unity. El resultado se envía a DSLE al finalizar.</p>
            @else
                {{-- Simulador: sin cliente real, permite enviar un resultado de prueba --}}
                <div class="immersive-sim" data-immersive-sim
                     data-complete-url="{{ $completeUrl }}"
                     data-max-score="{{ $experience->max_score }}">
                    <p class="text-muted">Modo simulador: introduce una puntuación para registrar el resultado.</p>
                    <div class="form__grid">
                        <label class="field">
                            <span class="field__label">Puntuación (0–{{ rtrim(rtrim($experience->max_score, '0'), '.') }})</span>
                            <input type="number" min="0" max="{{ $experience->max_score }}" step="0.01"
                                   class="field__control" data-sim-score value="{{ rtrim(rtrim($experience->max_score, '0'), '.') }}">
                        </label>
                    </div>
                    <button type="button" class="btn btn--primary" data-sim-submit>Finalizar experiencia</button>
                    <p class="field__error" data-sim-error hidden></p>
                </div>
            @endif

            <form method="POST" action="{{ route('student.immersive.abandon', $session) }}" class="u-mt-4"
                  data-confirm="¿Cerrar la sesión sin registrar resultado?">
                @csrf
                <button type="submit" class="link link--danger">Cerrar sin terminar</button>
            </form>
        </x-ui.card>
    @endif
@endsection
