<?php

namespace App\Services\Reports;

use App\Services\Reports\Contracts\Report;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exporta cualquier Report a CSV descargable. Sin dependencias: escribe con
 * fputcsv a la salida y añade BOM para que Excel respete el UTF-8.
 */
class ReportExporter
{
    public function csv(Report $report): StreamedResponse
    {
        $filename = $report->key().'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'wb');

            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($out, [$report->title()]);
            foreach ($report->meta() as $label => $value) {
                fputcsv($out, [$label, $value]);
            }
            fputcsv($out, []);

            fputcsv($out, $report->headings());
            foreach ($report->rows() as $row) {
                fputcsv($out, $row);
            }

            if ($report->summary() !== []) {
                fputcsv($out, []);
                fputcsv($out, ['Resumen']);
                foreach ($report->summary() as $label => $value) {
                    fputcsv($out, [$label, $value]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
