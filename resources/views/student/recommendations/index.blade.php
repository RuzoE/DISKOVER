@extends('layouts.app')

@section('title', 'Recomendaciones')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Recomendaciones para ti</h1>
            <p class="page-header__subtitle">Generadas por reglas a partir de tu progreso y tus notas (sin IA).</p>
        </div>
    </div>

    <div class="dashboard-grid">
        <x-dashboard.stat-card icon="clipboard" tone="blue" label="Abiertas" :value="$stats['open']" />
        <x-dashboard.stat-card icon="chart" tone="red" label="Prioridad alta" :value="$stats['high']" />
        <x-dashboard.stat-card icon="sparkles" tone="green" label="Resueltas" :value="$stats['completed']" />
    </div>

    <x-ui.card title="Pendientes y en curso">
        @if ($open->isEmpty())
            <x-tables.empty-state message="No hay recomendaciones abiertas. Vuelve más adelante o revisa tu analítica." />
        @else
            <div class="rec-list">
                @foreach ($open as $recommendation)
                    <x-recommendations.card :recommendation="$recommendation" />
                @endforeach
            </div>
        @endif
    </x-ui.card>

    @if ($resolved->isNotEmpty())
        <x-ui.card title="Historial de recomendaciones">
            <ul class="panel-list">
                @foreach ($resolved as $recommendation)
                    <li class="panel-list__item">
                        <span>{{ $recommendation->title }}
                            <span class="panel-list__meta">· {{ $recommendation->type->label() }}</span>
                        </span>
                        <span>
                            <x-ui.badge :color="$recommendation->status->badgeColor()">{{ $recommendation->status->label() }}</x-ui.badge>
                            <span class="panel-list__meta">{{ $recommendation->responded_at?->diffForHumans() }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
@endsection
