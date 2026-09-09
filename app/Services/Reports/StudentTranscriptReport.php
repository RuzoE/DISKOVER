<?php

namespace App\Services\Reports;

use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use App\Services\Analytics\ProgressCalculator;
use App\Services\Reports\Contracts\Report;

/**
 * Expediente del estudiante: una fila por asignatura de cada curso en el que
 * está inscrito, con su promedio y estado.
 */
class StudentTranscriptReport implements Report
{
    /** @var array<int, array{course: Course, subject: Subject, average: float|null, progress: float}> */
    private array $lines = [];

    public function __construct(
        private readonly User $student,
        private readonly ProgressCalculator $progress,
    ) {
        $courses = $student->enrolledCourses()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->orderBy('name')
            ->get();

        foreach ($courses as $course) {
            foreach ($course->subjects()->where('status', AcademicStatus::Active->value)->orderBy('position')->get() as $subject) {
                $row = $this->progress->forSubject($student, $subject);
                $this->lines[] = [
                    'course' => $course,
                    'subject' => $subject,
                    'average' => $row['average'],
                    'progress' => $row['percentage'],
                ];
            }
        }
    }

    public function key(): string
    {
        return 'expediente-'.$this->student->id;
    }

    public function title(): string
    {
        return 'Expediente académico — '.$this->student->name;
    }

    public function meta(): array
    {
        return [
            'Estudiante' => $this->student->name,
            'Correo' => $this->student->email,
            'Generado' => now()->format('d/m/Y H:i'),
        ];
    }

    public function headings(): array
    {
        return ['Curso', 'Asignatura', 'Progreso %', 'Promedio %', 'Estado'];
    }

    public function rows(): array
    {
        $pass = (float) config('dsle.analytics.pass_threshold', 60);

        return array_map(function (array $line) use ($pass) {
            $avg = $line['average'];

            return [
                $line['course']->name,
                $line['subject']->name,
                Fmt::num($line['progress']),
                $avg !== null ? Fmt::num($avg) : '—',
                $avg === null ? 'En curso' : ($avg >= $pass ? 'Aprobada' : 'No superada'),
            ];
        }, $this->lines);
    }

    public function summary(): array
    {
        $averages = collect($this->lines)->pluck('average')->filter(fn ($v) => $v !== null);

        return [
            'Cursos' => (string) collect($this->lines)->pluck('course.id')->unique()->count(),
            'Asignaturas' => (string) count($this->lines),
            'Promedio global' => $averages->isEmpty() ? '—' : Fmt::num($averages->avg()).' %',
        ];
    }
}
