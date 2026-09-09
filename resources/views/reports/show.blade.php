@extends('layouts.app')

@section('title', $report->title())

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">{{ $report->title() }}</h1>
            <p class="page-header__subtitle">
                @foreach ($report->meta() as $label => $value)
                    <span class="text-muted">{{ $label }}: <strong>{{ $value }}</strong></span>@if (! $loop->last) · @endif
                @endforeach
            </p>
        </div>
        <div class="u-flex u-gap-3">
            <x-ui.button :href="route('reports.index')" variant="ghost">Reportes</x-ui.button>
            <x-ui.button :href="$exportUrl" variant="primary">Exportar CSV</x-ui.button>
        </div>
    </div>

    @if ($report->summary() !== [])
        <div class="dashboard-grid">
            @foreach ($report->summary() as $label => $value)
                <x-dashboard.stat-card :label="$label" :value="$value" tone="neutral" />
            @endforeach
        </div>
    @endif

    <x-ui.card>
        @php $rows = $report->rows(); @endphp
        @if (empty($rows))
            <x-tables.empty-state message="No hay datos para este reporte." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            @foreach ($report->headings() as $heading)
                                <th>{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
