@props(['title' => null])

<section {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <header class="card__header">
            @if ($title)
                <h2 class="card__title">{{ $title }}</h2>
            @endif
            @isset($actions)
                <div class="card__actions">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="card__body">{{ $slot }}</div>
</section>
