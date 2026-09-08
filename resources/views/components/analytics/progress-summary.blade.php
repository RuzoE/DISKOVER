@props(['progress', 'label' => 'Progreso'])

<div class="progress-summary">
    <x-ui.progress :value="$progress['percentage']" :label="$label" />

    <div class="progress-summary__stats">
        <span><strong>{{ $progress['contents_done'] }}</strong>/{{ $progress['contents_total'] }} contenidos</span>
        <span><strong>{{ $progress['activities_graded'] }}</strong>/{{ $progress['activities_total'] }} actividades</span>
        <span>Promedio: <strong>{{ $progress['average'] !== null ? $progress['average'].'%' : '—' }}</strong></span>
        @if ($progress['pending'] > 0)
            <span class="text-muted">{{ $progress['pending'] }} pendiente(s)</span>
        @endif
        @if ($progress['overdue'] > 0)
            <span class="badge badge--red">{{ $progress['overdue'] }} vencida(s)</span>
        @endif
    </div>
</div>
