<?php

namespace Tests\Feature\Reports;

use App\Services\Reports\Contracts\Report;
use App\Services\Reports\ReportExporter;
use Tests\TestCase;

class ReportExporterTest extends TestCase
{
    private function sampleReport(): Report
    {
        return new class implements Report
        {
            public function key(): string
            {
                return 'demo-report';
            }

            public function title(): string
            {
                return 'Reporte de prueba';
            }

            public function meta(): array
            {
                return ['Curso' => 'Álgebra, grupo A'];
            }

            public function headings(): array
            {
                return ['Estudiante', 'Nota'];
            }

            public function rows(): array
            {
                return [
                    ['Ada "A." Lovelace', '95'],
                    ['Grace, Hopper', '88'],
                ];
            }

            public function summary(): array
            {
                return ['Promedio' => '91.5'];
            }
        };
    }

    public function test_csv_download_has_expected_shape(): void
    {
        $response = app(ReportExporter::class)->csv($this->sampleReport());

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('demo-report-', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv); // BOM UTF-8
        $this->assertStringContainsString('Reporte de prueba', $csv);
        $this->assertStringContainsString('Curso,"Álgebra, grupo A"', $csv);
        $this->assertStringContainsString('Estudiante,Nota', $csv);
        $this->assertStringContainsString('"Ada ""A."" Lovelace",95', $csv);
        $this->assertStringContainsString('"Grace, Hopper",88', $csv);
        $this->assertStringContainsString('Promedio,91.5', $csv);
    }
}
