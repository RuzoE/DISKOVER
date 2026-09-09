@extends('layouts.app')

@section('title', 'Asistente educativo')

@section('content')
    <div class="page-header">
        <h1 class="page-header__title">Asistente educativo</h1>
        <p class="page-header__subtitle">Resuelve dudas y recibe orientación personalizada según tu progreso.</p>
    </div>

    @unless ($realProvider)
        <x-ui.alert type="info" class="u-mb-4">
            El asistente funciona en <strong>modo sin conexión</strong>: da orientación a partir de tu contexto
            académico, sin llamar a ningún servicio externo. Configura <code>DSLE_AI_PROVIDER</code> y
            <code>DSLE_AI_API_KEY</code> en <code>.env</code> para respuestas generadas por IA.
        </x-ui.alert>
    @endunless

    <x-ui.card title="Nueva consulta">
        <form method="POST" action="{{ route('assistant.store') }}" class="form" data-ai-form>
            @csrf
            <div class="field">
                <label class="field__label" for="message">¿Qué necesitas?</label>
                <textarea name="message" id="message" rows="3" class="field__control" required
                          maxlength="4000" data-ai-input
                          placeholder="Ej.: ¿Cómo puedo prepararme para la evaluación de la unidad 1?">{{ old('message') }}</textarea>
                @error('message')<p class="field__error">{{ $message }}</p>@enderror
            </div>
            <div class="form__actions">
                <x-ui.button type="submit" variant="primary" data-ai-submit>Enviar</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Tus conversaciones">
        @if ($conversations->isEmpty())
            <x-tables.empty-state message="Aún no tienes conversaciones con el asistente." />
        @else
            <ul class="panel-list">
                @foreach ($conversations as $conversation)
                    <li class="panel-list__item">
                        <a href="{{ route('assistant.show', $conversation) }}" class="link">{{ $conversation->title }}</a>
                        <span class="panel-list__meta">
                            {{ $conversation->contextLabel() }} ·
                            {{ $conversation->messages_count }} mensaje(s) ·
                            {{ $conversation->last_message_at?->diffForHumans() }}
                        </span>
                    </li>
                @endforeach
            </ul>
            <div class="u-mt-4">{{ $conversations->links() }}</div>
        @endif
    </x-ui.card>
@endsection
