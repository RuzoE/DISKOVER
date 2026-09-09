<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Reports\AuditLogReport;
use App\Services\Reports\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Visor del registro de auditoría. Sólo lectura: la tabla `audit_logs` es
 * append-only. Acceso restringido por el permiso `audit.view` (ver ADR-0014).
 */
class AuditController extends Controller
{
    public function __construct(private readonly ReportExporter $exporter) {}

    public function index(Request $request): View|Response
    {
        $filters = [
            'event' => $request->string('event')->toString() ?: null,
            'user_id' => $request->integer('user_id') ?: null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
            'q' => $request->string('q')->toString() ?: null,
        ];

        $report = new AuditLogReport($filters);

        if ($request->query('export') === 'csv') {
            return $this->exporter->csv($report);
        }

        $logs = AuditLog::query()
            ->with('user')
            ->when($filters['event'], fn ($q, $event) => $q->where('event', $event))
            ->when($filters['user_id'], fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['from'], fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['q'], fn ($q, $text) => $q->where(
                fn ($sub) => $sub->where('description', 'like', "%{$text}%")
                    ->orWhere('event', 'like', "%{$text}%")
                    ->orWhere('auditable_type', 'like', "%{$text}%"),
            ))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'filters' => $filters,
            'events' => AuditLog::query()->distinct()->orderBy('event')->pluck('event'),
            'actors' => User::query()
                ->whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'summary' => $report->summary(),
            'exportUrl' => $request->fullUrlWithQuery(['export' => 'csv']),
        ]);
    }
}
