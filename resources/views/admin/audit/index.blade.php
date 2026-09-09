@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-header__title">Registro de auditoría</h1>
            <p class="page-header__subtitle">Rastro de accesos y cambios sobre entidades sensibles. Sólo lectura.</p>
        </div>
        <x-ui.button :href="$exportUrl" variant="primary">Exportar CSV</x-ui.button>
    </div>

    <div class="dashboard-grid">
        @foreach ($summary as $label => $value)
            <x-dashboard.stat-card :label="$label" :value="$value" tone="neutral" />
        @endforeach
    </div>

    <x-ui.card>
        <form method="GET" action="{{ route('admin.audit.index') }}" class="filters">
            <select name="event" class="field__control" aria-label="Filtrar por evento">
                <option value="">Todos los eventos</option>
                @foreach ($events as $event)
                    <option value="{{ $event }}" @selected(($filters['event'] ?? '') === $event)>{{ $event }}</option>
                @endforeach
            </select>

            <select name="user_id" class="field__control" aria-label="Filtrar por actor">
                <option value="">Todos los actores</option>
                @foreach ($actors as $actor)
                    <option value="{{ $actor->id }}" @selected((int) ($filters['user_id'] ?? 0) === $actor->id)>{{ $actor->name }}</option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="field__control" aria-label="Desde">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="field__control" aria-label="Hasta">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar en descripción" class="field__control" aria-label="Buscar">

            <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
            <x-ui.button :href="route('admin.audit.index')" variant="ghost">Limpiar</x-ui.button>
        </form>

        @if ($logs->isEmpty())
            <x-tables.empty-state message="No hay eventos que coincidan con los filtros." />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Evento</th>
                            <th>Actor</th>
                            <th>Objeto</th>
                            <th>Descripción</th>
                            <th>IP</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td><code>{{ $log->event }}</code></td>
                                <td>{{ $log->actorName() }}</td>
                                <td>{{ $log->targetLabel() ?? '—' }}</td>
                                <td>{{ $log->description ?? '—' }}</td>
                                <td>{{ $log->ip_address ?? '—' }}</td>
                                <td>
                                    @if ($log->properties)
                                        <details>
                                            <summary class="link">Ver</summary>
                                            <pre class="code-block">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </details>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="u-mt-4">{{ $logs->links() }}</div>
        @endif
    </x-ui.card>
@endsection
