<?php

namespace App\Services\Reports;

use App\Models\Course;
use App\Services\Analytics\CourseAnalyticsService;
use App\Services\Reports\Contracts\Report;

/**
 * Reporte académico de un curso: estado de cada estudiante inscrito
 * (progreso, promedio, actividades, contenidos, aprobado/no).
 */
class CourseAcademicReport implements Report
{
    /** @var array<int, array<string, mixed>> */
    private array $overview;

    public function __construct(
        private readonly Course $course,
        CourseAnalyticsService $analytics,
    ) {
        $this->overview = $analytics->courseOverview($course);
    }

    public function key(): string
    {
        return 'reporte-academico-curso-'.$this->course->code;
    }

    public function title(): string
    {
        return 'Reporte académico — '.$this->course->name;
    }

    public function meta(): array
    {
        return [
            'Curso' => $this->course->name.' ('.$this->course->code.')',
            'Estado' => $this->course->status->label(),
            'Generado' => now()->format('d/m/Y H:i'),
        ];
    }

    public function headings(): array
    {
        return ['Estudiante', 'Progreso %', 'Promedio %', 'Actividades calificadas', 'Contenidos completados', 'Pendientes', 'Vencidas', 'Estado'];
    }

    public function rows(): array
    {
        $pass = (float) config('dsle.analytics.pass_threshold', 60);

        return array_map(function (array $row) use ($pass) {
            $p = $row['progress'];
            $avg = $p['average'];

            return [
                $row['student']->name,
                Fmt::num($p['percentage']),
                $avg !== null ? Fmt::num($avg) : '—',
                $p['activities_graded'].'/'.$p['activities_total'],
                $p['contents_done'].'/'.$p['contents_total'],
                (string) $p['pending'],
                (string) $p['overdue'],
                $avg === null ? 'Sin nota' : ($avg >= $pass ? 'Aprobado' : 'No superado'),
            ];
        }, $this->overview['rows']);
    }

    public function summary(): array
    {
        return [
            'Estudiantes' => (string) count($this->overview['rows']),
            'Avance medio' => Fmt::num($this->overview['average_progress']).' %',
            'Promedio del curso' => $this->overview['average'] !== null ? Fmt::num($this->overview['average']).' %' : '—',
        ];
    }
}
