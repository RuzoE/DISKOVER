<?php

namespace App\Services\Reports;

use App\Enums\EnrollmentStatus;
use App\Models\Activity;
use App\Models\Subject;
use App\Models\User;
use App\Services\Analytics\ProgressCalculator;
use App\Services\Reports\Contracts\Report;
use Illuminate\Support\Collection;

/**
 * Reporte de desempeño de una asignatura: matriz estudiante × actividad con la
 * nota de cada actividad publicada, más el promedio de la asignatura.
 */
class SubjectPerformanceReport implements Report
{
    /** @var Collection<int, Activity> */
    private $activities;

    /** @var Collection<int, User> */
    private $students;

    public function __construct(
        private readonly Subject $subject,
        private readonly ProgressCalculator $progress,
    ) {
        $this->activities = $subject->activities()->where('is_published', true)->get();
        $this->students = $subject->course->students()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->orderBy('name')
            ->get();
    }

    public function key(): string
    {
        return 'reporte-desempeno-asignatura-'.$this->subject->code;
    }

    public function title(): string
    {
        return 'Reporte de desempeño — '.$this->subject->name;
    }

    public function meta(): array
    {
        return [
            'Asignatura' => $this->subject->name.' ('.$this->subject->code.')',
            'Curso' => $this->subject->course->name,
            'Docente' => $this->subject->teacher?->name ?? 'Sin asignar',
            'Generado' => now()->format('d/m/Y H:i'),
        ];
    }

    public function headings(): array
    {
        return array_merge(
            ['Estudiante'],
            $this->activities->map(fn ($a) => $a->title.' (/'.Fmt::num($a->max_score, 0).')')->all(),
            ['Promedio %'],
        );
    }

    public function rows(): array
    {
        return $this->students->map(function ($student) {
            $grades = $student->grades()
                ->whereIn('activity_id', $this->activities->pluck('id'))
                ->get()
                ->keyBy('activity_id');

            $cells = [$student->name];
            foreach ($this->activities as $activity) {
                $grade = $grades->get($activity->id);
                $cells[] = $grade ? Fmt::num($grade->score, 2) : '—';
            }
            $cells[] = Fmt::num($this->progress->forSubject($student, $this->subject)['average']);

            return $cells;
        })->all();
    }

    public function summary(): array
    {
        $averages = $this->students
            ->map(fn ($s) => $this->progress->forSubject($s, $this->subject)['average'])
            ->filter(fn ($v) => $v !== null);

        return [
            'Estudiantes' => (string) $this->students->count(),
            'Actividades publicadas' => (string) $this->activities->count(),
            'Promedio de la asignatura' => $averages->isEmpty() ? '—' : Fmt::num($averages->avg()).' %',
        ];
    }
}
