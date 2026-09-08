<?php

namespace App\Services\Analytics;

use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Análisis de desempeño a partir de las calificaciones: evolución en el tiempo,
 * desglose por asignatura, distribución de notas e identificación de dificultades.
 */
class PerformanceAnalyticsService
{
    public function __construct(private readonly ProgressCalculator $progress) {}

    private function passThreshold(): float
    {
        return (float) config('dsle.analytics.pass_threshold', 60);
    }

    private function atRiskThreshold(): float
    {
        return (float) config('dsle.analytics.at_risk_threshold', 60);
    }

    /**
     * Informe de desempeño de un estudiante en todos sus cursos activos.
     *
     * @return array<string, mixed>
     */
    public function studentReport(User $student): array
    {
        $courses = $student->enrolledCourses()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->orderBy('name')
            ->get();

        $grades = $student->grades()->with('activity.subject')->get();

        $bySubject = [];
        $subjectAverages = [];
        foreach ($courses as $course) {
            foreach ($course->subjects()->where('status', AcademicStatus::Active->value)->get() as $subject) {
                $row = $this->progress->forSubject($student, $subject);
                $bySubject[] = ['label' => $subject->name, 'value' => $row['average'] ?? 0, 'has_data' => $row['average'] !== null];
                if ($row['average'] !== null) {
                    $subjectAverages[] = ['subject' => $subject, 'average' => $row['average']];
                }
            }
        }

        $percentages = $grades->map(fn (Grade $g) => $this->gradePercent($g))->filter(fn ($v) => $v !== null);

        return [
            'evolution' => $this->evolution($grades),
            'by_subject' => $bySubject,
            'distribution' => $this->distribution($percentages),
            'weak_activities' => $grades
                ->filter(fn (Grade $g) => ($p = $this->gradePercent($g)) !== null && $p < $this->passThreshold())
                ->sortBy(fn (Grade $g) => $this->gradePercent($g))
                ->map(fn (Grade $g) => [
                    'title' => $g->activity->title,
                    'subject' => $g->activity->subject->name,
                    'percent' => $this->gradePercent($g),
                ])->values(),
            'weak_subjects' => collect($subjectAverages)
                ->filter(fn ($r) => $r['average'] < $this->passThreshold())
                ->sortBy('average')
                ->map(fn ($r) => ['name' => $r['subject']->name, 'average' => $r['average']])
                ->values(),
            'overall_average' => $percentages->isEmpty() ? null : round($percentages->avg(), 2),
            'graded_count' => $percentages->count(),
        ];
    }

    /**
     * Informe de desempeño de un curso (coordinación).
     *
     * @return array<string, mixed>
     */
    public function courseReport(Course $course): array
    {
        $students = $course->students()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->orderBy('name')
            ->get();

        $subjects = $course->subjects()->where('status', AcademicStatus::Active->value)->get();

        $subjectAverages = [];
        foreach ($subjects as $subject) {
            $vals = [];
            foreach ($students as $student) {
                $avg = $this->progress->forSubject($student, $subject)['average'];
                if ($avg !== null) {
                    $vals[] = $avg;
                }
            }
            $subjectAverages[] = [
                'label' => $subject->name,
                'value' => $vals === [] ? 0 : round(array_sum($vals) / count($vals), 2),
                'has_data' => $vals !== [],
            ];
        }

        $studentRows = [];
        foreach ($students as $student) {
            $avg = $this->progress->forCourse($student, $course)['average'];
            $studentRows[] = ['student' => $student, 'average' => $avg];
        }

        $allPercentages = collect($studentRows)->pluck('average')->filter(fn ($v) => $v !== null);

        return [
            'by_subject' => $subjectAverages,
            'distribution' => $this->distribution($allPercentages),
            'evolution' => $this->evolution(
                Grade::query()
                    ->whereHas('activity.subject', fn ($q) => $q->where('course_id', $course->id))
                    ->with('activity')
                    ->orderBy('graded_at')
                    ->get()
            ),
            'at_risk' => collect($studentRows)
                ->filter(fn ($r) => $r['average'] !== null && $r['average'] < $this->atRiskThreshold())
                ->sortBy('average')
                ->map(fn ($r) => ['name' => $r['student']->name, 'average' => $r['average']])
                ->values(),
            'course_average' => $allPercentages->isEmpty() ? null : round($allPercentages->avg(), 2),
            'students_count' => $students->count(),
        ];
    }

    /**
     * Distribución de notas en tramos de 10 puntos porcentuales.
     *
     * @param  Collection<int, float>  $percentages
     * @return array<int, array{label: string, value: int}>
     */
    public function distribution(Collection $percentages): array
    {
        $buckets = [
            '0–59' => 0, '60–69' => 0, '70–79' => 0, '80–89' => 0, '90–100' => 0,
        ];

        foreach ($percentages as $p) {
            $buckets[match (true) {
                $p < 60 => '0–59',
                $p < 70 => '60–69',
                $p < 80 => '70–79',
                $p < 90 => '80–89',
                default => '90–100',
            }]++;
        }

        return array_map(
            fn ($label, $value) => ['label' => $label, 'value' => $value],
            array_keys($buckets),
            array_values($buckets),
        );
    }

    /**
     * Serie temporal: una nota por punto, en orden cronológico de calificación.
     *
     * @param  Collection<int, Grade>  $grades
     * @return array<int, array{label: string, value: float}>
     */
    private function evolution(Collection $grades): array
    {
        return $grades
            ->filter(fn (Grade $g) => $g->graded_at !== null && $this->gradePercent($g) !== null)
            ->sortBy('graded_at')
            ->values()
            ->map(fn (Grade $g) => [
                'label' => $g->graded_at->format('d/m'),
                'value' => $this->gradePercent($g),
            ])
            ->all();
    }

    private function gradePercent(Grade $grade): ?float
    {
        $max = (float) $grade->activity->max_score;

        return $max > 0 ? round((float) $grade->score / $max * 100, 2) : null;
    }
}
