@extends('layouts.app')

@section('title', $activity->title)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $activity->title }}</h1>
            <p class="page-header__subtitle">
                {{ $activity->subject->name }} · {{ $activity->subject->course->name }} ·
                <x-ui.badge :color="$activity->isQuiz() ? 'blue' : 'neutral'">{{ $activity->type->label() }}</x-ui.badge>
                <x-ui.badge :color="$activity->is_published ? 'green' : 'amber'">{{ $activity->is_published ? 'Publicada' : 'Borrador' }}</x-ui.badge>
            </p>
        </div>
        <div class="u-flex u-gap-3">
            <x-ui.button :href="route('teacher.activities.gradebook', $activity)" variant="secondary">Cuaderno</x-ui.button>
            <x-ui.button :href="route('teacher.activities.edit', $activity)" variant="primary">Editar</x-ui.button>
        </div>
    </div>

    <x-ui.card title="Detalle">
        <dl class="detail-list">
            <div class="detail-list__row"><dt>Descripción</dt><dd>{{ $activity->description ?? '—' }}</dd></div>
            <div class="detail-list__row"><dt>Instrucciones</dt><dd>{{ $activity->instructions ?? '—' }}</dd></div>
            <div class="detail-list__row"><dt>Puntuación máxima</dt><dd>{{ rtrim(rtrim($activity->max_score, '0'), '.') }}</dd></div>
            <div class="detail-list__row"><dt>Apertura</dt><dd>{{ $activity->opens_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div class="detail-list__row"><dt>Entrega</dt><dd>{{ $activity->due_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
        </dl>
    </x-ui.card>

    @if ($activity->isQuiz())
        <x-ui.card title="Evaluación en línea">
            <x-slot:actions>
                <x-ui.button :href="route('teacher.activities.evaluation.edit', $activity)" variant="primary" class="btn--sm">
                    Configurar y preguntas
                </x-ui.button>
            </x-slot:actions>

            @php $ev = $activity->evaluation; @endphp
            <dl class="detail-list">
                <div class="detail-list__row"><dt>Preguntas</dt><dd>{{ $ev?->questions->count() ?? 0 }}</dd></div>
                <div class="detail-list__row"><dt>Puntos totales</dt><dd>{{ $ev?->totalScore() ?? 0 }}</dd></div>
                <div class="detail-list__row"><dt>Intentos permitidos</dt><dd>{{ $ev?->max_attempts }}</dd></div>
                <div class="detail-list__row"><dt>Tiempo límite</dt><dd>{{ $ev?->time_limit_minutes ? $ev->time_limit_minutes.' min' : 'Sin límite' }}</dd></div>
            </dl>
        </x-ui.card>
    @endif
@endsection
