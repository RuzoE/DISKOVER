@props(['events'])

@if ($events->isEmpty())
    <x-tables.empty-state message="Todavía no hay actividad registrada." />
@else
    <ol class="timeline">
        @foreach ($events as $event)
            <li class="timeline__item">
                <span class="timeline__icon"><x-ui.icon :name="$event->type->icon()" :size="16" /></span>
                <div class="timeline__body">
                    <p class="timeline__text">{{ $event->description }}</p>
                    <p class="timeline__meta">
                        {{ $event->type->label() }}
                        @if ($event->course) · {{ $event->course->name }} @endif
                        · {{ $event->occurred_at->diffForHumans() }}
                    </p>
                </div>
            </li>
        @endforeach
    </ol>
@endif
