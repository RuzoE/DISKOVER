@props([
    'label',
    'value',
    'icon' => null,
    'hint' => null,
    'tone' => 'blue', // blue | green | amber | red | neutral
])

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    @if ($icon)
        <span class="stat-card__icon stat-card__icon--{{ $tone }}">
            <x-ui.icon :name="$icon" :size="20" />
        </span>
    @endif
    <div class="stat-card__body">
        <p class="stat-card__label">{{ $label }}</p>
        <p class="stat-card__value">{{ $value }}</p>
        @if ($hint)
            <p class="stat-card__hint">{{ $hint }}</p>
        @endif
    </div>
</div>
