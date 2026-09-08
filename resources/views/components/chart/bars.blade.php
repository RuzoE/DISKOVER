@props([
    'series' => [],      // [ ['label' => .., 'value' => .., 'has_data' => bool?], ... ]
    'max' => null,       // eje; por defecto 100 o el mayor valor
    'unit' => '%',
])

@php
    $values = array_map(fn ($r) => (float) ($r['value'] ?? 0), $series);
    $scale = $max ?? max(100, $values ? max($values) : 0);
    $scale = $scale > 0 ? $scale : 1;
@endphp

@if (empty($series))
    <x-tables.empty-state message="Sin datos suficientes para el gráfico." />
@else
    <div {{ $attributes->merge(['class' => 'chart-bars']) }}>
        @foreach ($series as $row)
            @php
                $value = (float) ($row['value'] ?? 0);
                $hasData = $row['has_data'] ?? true;
                $pct = min(100, $value / $scale * 100);
                $tone = ! $hasData ? 'muted' : ($value >= 80 ? 'green' : ($value >= 60 ? 'blue' : 'amber'));
            @endphp
            <div class="chart-bars__row">
                <span class="chart-bars__label">{{ $row['label'] }}</span>
                <span class="chart-bars__track">
                    <span class="chart-bars__fill chart-bars__fill--{{ $tone }}" style="width: {{ $pct }}%"></span>
                </span>
                <span class="chart-bars__value">
                    {{ $hasData ? rtrim(rtrim(number_format($value, 1), '0'), '.').$unit : '—' }}
                </span>
            </div>
        @endforeach
    </div>
@endif
