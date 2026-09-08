@props([
    'points' => [],   // [ ['label' => .., 'value' => ..], ... ]
    'max' => 100,
    'unit' => '%',
])

@php
    $n = count($points);
    $w = 640; $h = 220;
    $padL = 34; $padR = 12; $padT = 12; $padB = 26;
    $innerW = $w - $padL - $padR;
    $innerH = $h - $padT - $padB;
    $scale = $max > 0 ? $max : 1;

    $coords = [];
    foreach (array_values($points) as $i => $p) {
        $x = $n > 1 ? $padL + ($i / ($n - 1)) * $innerW : $padL + $innerW / 2;
        $y = $padT + $innerH - (min($scale, (float) $p['value']) / $scale) * $innerH;
        $coords[] = ['x' => round($x, 1), 'y' => round($y, 1), 'label' => $p['label'], 'value' => (float) $p['value']];
    }
    $labelEvery = max(1, (int) ceil($n / 6));
@endphp

@if ($n === 0)
    <x-tables.empty-state message="Aún no hay calificaciones para trazar la evolución." />
@else
    <div class="chart-line" {{ $attributes }}>
        <svg viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="Evolución del desempeño" preserveAspectRatio="xMidYMid meet">
            @foreach ([0, 25, 50, 75, 100] as $g)
                @php $gy = $padT + $innerH - ($g / 100) * $innerH; @endphp
                <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $w - $padR }}" y2="{{ $gy }}" class="chart-line__grid" />
                <text x="{{ $padL - 6 }}" y="{{ $gy + 3 }}" text-anchor="end" class="chart-line__axis">{{ $g }}</text>
            @endforeach

            @if ($n > 1)
                <polyline class="chart-line__path"
                          points="@foreach ($coords as $c){{ $c['x'] }},{{ $c['y'] }} @endforeach" />
            @endif

            @foreach ($coords as $i => $c)
                <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="3.5" class="chart-line__dot">
                    <title>{{ $c['label'] }}: {{ rtrim(rtrim(number_format($c['value'], 1), '0'), '.') }}{{ $unit }}</title>
                </circle>
                @if ($i % $labelEvery === 0 || $i === $n - 1)
                    <text x="{{ $c['x'] }}" y="{{ $h - 8 }}" text-anchor="middle" class="chart-line__axis">{{ $c['label'] }}</text>
                @endif
            @endforeach
        </svg>
    </div>
@endif
