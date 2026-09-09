@props(['recommendation'])

@php $r = $recommendation; @endphp

<article class="rec-card rec-card--{{ $r->priority->value }}">
    <div class="rec-card__head">
        <span class="rec-card__icon"><x-ui.icon :name="$r->type->icon()" :size="18" /></span>
        <div>
            <p class="rec-card__title">{{ $r->title }}</p>
            <p class="rec-card__meta">
                <x-ui.badge :color="$r->priority->badgeColor()">Prioridad {{ $r->priority->label() }}</x-ui.badge>
                <x-ui.badge :color="$r->status->badgeColor()">{{ $r->status->label() }}</x-ui.badge>
                <span class="text-muted">{{ $r->type->label() }}</span>
            </p>
        </div>
    </div>

    <p class="rec-card__body">{{ $r->body }}</p>

    <div class="rec-card__actions">
        @if ($url = $r->actionUrl())
            <x-ui.button :href="$url" variant="secondary" class="btn--sm">Ir a la actividad</x-ui.button>
        @endif

        <form method="POST" action="{{ route('student.recommendations.respond', $r) }}" class="u-flex u-gap-3">
            @csrf
            @if ($r->status === App\Enums\RecommendationStatus::Pending)
                <button type="submit" name="action" value="accept" class="btn btn--primary btn--sm">Marcar en curso</button>
            @endif
            <button type="submit" name="action" value="complete" class="btn btn--secondary btn--sm">Hecho</button>
            <button type="submit" name="action" value="dismiss" class="link link--danger">Descartar</button>
        </form>
    </div>
</article>
