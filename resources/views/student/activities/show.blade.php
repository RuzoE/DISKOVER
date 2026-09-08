@extends('layouts.app')

@section('title', $activity->title)

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $activity->title }}</h1>
            <p class="page-header__subtitle">
                <a href="{{ route('student.subjects.show', $activity->subject) }}" class="link">{{ $activity->subject->name }}</a>
                · <x-ui.badge :color="$activity->isQuiz() ? 'blue' : 'neutral'">{{ $activity->type->label() }}</x-ui.badge>
            </p>
        </div>
    </div>

    <x-ui.card title="Descripción">
        <p>{{ $activity->description ?? 'Sin descripción.' }}</p>
        @if ($activity->instructions)
            <p class="u-mt-4"><strong>Instrucciones:</strong> {{ $activity->instructions }}</p>
        @endif
        <dl class="detail-list u-mt-4">
            <div class="detail-list__row"><dt>Puntuación máxima</dt><dd>{{ rtrim(rtrim($activity->max_score, '0'), '.') }}</dd></div>
            <div class="detail-list__row"><dt>Entrega</dt>
                <dd>
                    {{ $activity->due_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                    @if ($activity->isPastDue()) <x-ui.badge color="red">Cerrada</x-ui.badge> @endif
                </dd>
            </div>
        </dl>
    </x-ui.card>

    @if ($grade)
        <x-ui.card title="Tu calificación">
            <p class="grade-big">{{ rtrim(rtrim($grade->score, '0'), '.') }} <span>/ {{ rtrim(rtrim($activity->max_score, '0'), '.') }}</span></p>
            @if ($grade->feedback)
                <p class="u-mt-4"><strong>Comentario del docente:</strong> {{ $grade->feedback }}</p>
            @endif
        </x-ui.card>
    @endif

    @if ($activity->isQuiz())
        <x-ui.card title="Evaluación en línea">
            @php $ev = $activity->evaluation; @endphp
            <dl class="detail-list">
                <div class="detail-list__row"><dt>Preguntas</dt><dd>{{ $ev?->questions->count() }}</dd></div>
                <div class="detail-list__row"><dt>Intentos</dt><dd>{{ $attempts->count() }} / {{ $ev?->max_attempts }}</dd></div>
                <div class="detail-list__row"><dt>Tiempo límite</dt><dd>{{ $ev?->time_limit_minutes ? $ev->time_limit_minutes.' min' : 'Sin límite' }}</dd></div>
            </dl>

            @if ($attempts->isNotEmpty())
                <ul class="content-list u-mt-4">
                    @foreach ($attempts as $att)
                        <li class="content-list__item">
                            <span class="content-list__title">Intento #{{ $att->number }}</span>
                            <x-ui.badge :color="$att->status->badgeColor()">{{ $att->status->label() }}</x-ui.badge>
                            @if ($att->score !== null)
                                <span>{{ rtrim(rtrim($att->score, '0'), '.') }} / {{ rtrim(rtrim($att->max_score, '0'), '.') }}</span>
                            @endif
                            <a href="{{ route('student.attempts.show', $att) }}" class="link">
                                {{ $att->status->isOpen() ? 'Continuar' : 'Ver resultado' }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @php
                $canStart = $activity->isOpenNow()
                    && $attempts->count() < ($ev?->max_attempts ?? 1)
                    && ! $attempts->firstWhere('status', App\Enums\AttemptStatus::InProgress);
            @endphp

            @if ($canStart)
                <form method="POST" action="{{ route('student.activities.attempts.store', $activity) }}" class="u-mt-4">
                    @csrf
                    <x-ui.button type="submit" variant="primary">Comenzar intento</x-ui.button>
                </form>
            @elseif (! $activity->isOpenNow())
                <p class="text-muted u-mt-4">La evaluación no está disponible en este momento.</p>
            @elseif ($attempts->count() >= ($ev?->max_attempts ?? 1))
                <p class="text-muted u-mt-4">Has agotado los intentos.</p>
            @endif
        </x-ui.card>
    @endif
@endsection
