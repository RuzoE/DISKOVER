@props([
    'value' => 0,   // 0-100
    'label' => null,
])

@php
    $pct = max(0, min(100, (float) $value));
    $tone = $pct >= 80 ? 'green' : ($pct >= 40 ? 'blue' : 'amber');
@endphp

<div {{ $attributes->merge(['class' => 'progress']) }}>
    @if ($label !== null)
        <div class="progress__head">
            <span>{{ $label }}</span>
            <span>{{ rtrim(rtrim(number_format($pct, 1), '0'), '.') }}%</span>
        </div>
    @endif
    <div class="progress__track" role="progressbar" aria-valuenow="{{ round($pct) }}" aria-valuemin="0" aria-valuemax="100">
        <div class="progress__bar progress__bar--{{ $tone }}" style="width: {{ $pct }}%"></div>
    </div>
</div>
