@extends('layouts.app')

@section('title', $conversation->title)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $conversation->title }}</h1>
            <p class="page-header__subtitle">
                {{ $conversation->contextLabel() }} ·
                {{ $conversation->provider ?? 'stub' }}@if ($conversation->model) · {{ $conversation->model }}@endif
            </p>
        </div>
        <div class="u-flex u-gap-3">
            <x-ui.button :href="route('assistant.index')" variant="ghost">Volver</x-ui.button>
            <form method="POST" action="{{ route('assistant.destroy', $conversation) }}"
                  data-confirm="¿Eliminar esta conversación?">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn--ghost">Eliminar</button>
            </form>
        </div>
    </div>

    @unless ($realProvider)
        <x-ui.alert type="info" class="u-mb-4">Modo sin conexión: respuestas orientativas basadas en tu contexto.</x-ui.alert>
    @endunless

    <x-ui.card>
        <div class="chat-thread" data-ai-thread>
            @foreach ($messages as $message)
                <x-ai.bubble :message="$message" />
            @endforeach
        </div>

        <form method="POST" action="{{ route('assistant.message', $conversation) }}" class="chat-composer" data-ai-form>
            @csrf
            <textarea name="message" rows="2" class="field__control" required maxlength="4000"
                      data-ai-input placeholder="Escribe tu mensaje…">{{ old('message') }}</textarea>
            @error('message')<p class="field__error">{{ $message }}</p>@enderror
            <x-ui.button type="submit" variant="primary" data-ai-submit>Enviar</x-ui.button>
        </form>
    </x-ui.card>
@endsection
