<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Reports\PersonalDataReport;
use App\Services\Reports\ReportExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Descarga de los datos personales de la persona autenticada (portabilidad).
 * Cada quien sólo puede exportar su propia información (ADR-0014).
 */
class DataExportController extends Controller
{
    public function __construct(private readonly ReportExporter $exporter) {}

    public function download(Request $request): Response
    {
        $report = new PersonalDataReport($request->user());

        return $this->exporter->csv($report);
    }
}
