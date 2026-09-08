<?php

namespace App\Services\Analytics;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\LearningEvent;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Indicadores de seguimiento de un estudiante: progreso por curso, perfil
 * académico global e historial de aprendizaje.
 */
class StudentAnalyticsService
{
    public function __construct(private readonly ProgressCalculator $calculator) {}

    /**
     * @return array<string, mixed>
     */
    public function courseProgress(User $student, Course $course): array
    {
        return $this->calculator->forCourse($student, $course);
    }

    /**
     * Perfil académico: resumen de todos los cursos activos del estudiante.
     *
     * @return array<string, mixed>
     */
    public function profile(User $student): array
    {
        $courses = $student->enrolledCourses()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->orderBy('name')
            ->get();

        $rows = [];
        $averages = [];
        $totals = ['pending' => 0, 'overdue' => 0, 'graded' => 0, 'items_done' => 0, 'items_total' => 0];

        foreach ($courses as $course) {
            $progress = $this->calculator->forCourse($student, $course);
            $rows[] = ['course' => $course, 'progress' => $progress];

            $totals['pending'] += $progress['pending'];
            $totals['overdue'] += $progress['overdue'];
            $totals['graded'] += $progress['activities_graded'];
            $totals['items_done'] += $progress['items_done'];
            $totals['items_total'] += $progress['items_total'];

            if ($progress['average'] !== null) {
                $averages[] = $progress['average'];
            }
        }

        return [
            'courses' => $rows,
            'overall_average' => $averages === [] ? null : round(array_sum($averages) / count($averages), 2),
            'overall_percentage' => $totals['items_total'] > 0
                ? round($totals['items_done'] / $totals['items_total'] * 100, 1)
                : 0.0,
            'courses_count' => $courses->count(),
            'pending' => $totals['pending'],
            'overdue' => $totals['overdue'],
            'activities_graded' => $totals['graded'],
        ];
    }

    /**
     * @return Collection<int, LearningEvent>
     */
    public function history(User $student, int $limit = 30): Collection
    {
        return $student->learningEvents()
            ->with(['course', 'subject', 'activity'])
            ->limit($limit)
            ->get();
    }
}
