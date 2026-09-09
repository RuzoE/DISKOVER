<?php

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Services\Reports\Contracts\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Registro de auditoría filtrable (evento, actor, rango de fechas y texto libre).
 * Se renderiza en pantalla y se exporta a CSV con el mismo objeto.
 */
class AuditLogReport implements Report
{
    /**
     * @param  array{event?: ?string, user_id?: ?int, from?: ?string, to?: ?string, q?: ?string}  $filters
     */
    public function __construct(private readonly array $filters = []) {}

    public function key(): string
    {
        return 'auditoria';
    }

    public function title(): string
    {
        return 'Registro de auditoría DSLE';
    }

    public function meta(): array
    {
        $meta = ['Generado' => now()->format('d/m/Y H:i')];

        if (! empty($this->filters['event'])) {
            $meta['Evento'] = $this->filters['event'];
        }
        if (! empty($this->filters['from'])) {
            $meta['Desde'] = $this->filters['from'];
        }
        if (! empty($this->filters['to'])) {
            $meta['Hasta'] = $this->filters['to'];
        }
        if (! empty($this->filters['q'])) {
            $meta['Búsqueda'] = $this->filters['q'];
        }

        return $meta;
    }

    public function headings(): array
    {
        return ['Fecha', 'Evento', 'Actor', 'Objeto', 'Descripción', 'IP'];
    }

    public function rows(): array
    {
        return $this->query()
            ->with('user')
            ->orderByDesc('id')
            ->limit(5000)
            ->get()
            ->map(fn (AuditLog $log) => [
                $log->created_at?->format('d/m/Y H:i:s'),
                $log->event,
                $log->actorName(),
                $log->targetLabel() ?? '—',
                $log->description ?? '—',
                $log->ip_address ?? '—',
            ])
            ->all();
    }

    public function summary(): array
    {
        $base = $this->query();

        return [
            'Eventos en el resultado' => (string) (clone $base)->count(),
            'Actores distintos' => (string) (clone $base)->distinct()->count('user_id'),
            'Tipos de evento' => (string) (clone $base)->distinct()->count('event'),
        ];
    }

    /**
     * @return Builder<AuditLog>
     */
    private function query()
    {
        return AuditLog::query()
            ->when($this->filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($this->filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when(
                $this->filters['from'] ?? null,
                fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()),
            )
            ->when(
                $this->filters['to'] ?? null,
                fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()),
            )
            ->when($this->filters['q'] ?? null, fn ($q, $text) => $q->where(
                fn ($sub) => $sub->where('description', 'like', "%{$text}%")
                    ->orWhere('event', 'like', "%{$text}%")
                    ->orWhere('auditable_type', 'like', "%{$text}%"),
            ));
    }
}
