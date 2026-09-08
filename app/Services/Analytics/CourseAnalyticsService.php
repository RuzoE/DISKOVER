<?php

namespace App\Services\Analytics;

use App\Models\Course;
use App\Models\Subject;

/**
 * Seguimiento agregado a nivel de curso (coordinación) y de asignatura (docente):
 * progreso y promedio de cada estudiante inscrito.
 */
class CourseAnalyticsService
{
    public function __construct(private readonly ProgressCalculator $calculator) {}

    /**
     * @return array{rows: array<int, array<string, mixed>>, average: float|null, average_progress: float}
     */
    public function courseOverview(Course $course): array
    {
        $students = $course->students()
            ->wherePivotIn('status', ['active', 'completed'])
            ->orderBy('name')
            ->get();

        $rows = [];
        $averages = [];
        $progresses = [];

        foreach ($students as $student) {
            $progress = $this->calculator->forCourse($student, $course);
            $rows[] = ['student' => $student, 'progress' => $progress];
            $progresses[] = $progress['percentage'];
            if ($progress['average'] !== null) {
                $averages[] = $progress['average'];
            }
        }

        return [
            'rows' => $rows,
            'average' => $averages === [] ? null : round(array_sum($averages) / count($averages), 2),
            'average_progress' => $progresses === [] ? 0.0 : round(array_sum($progresses) / count($progresses), 1),
        ];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, average: float|null}
     */
    public function subjectOverview(Subject $subject): array
    {
        $students = $subject->course->students()
            ->wherePivotIn('status', ['active', 'completed'])
            ->orderBy('name')
            ->get();

        $rows = [];
        $averages = [];

        foreach ($students as $student) {
            $progress = $this->calculator->forSubject($student, $subject);
            $rows[] = ['student' => $student, 'progress' => $progress];
            if ($progress['average'] !== null) {
                $averages[] = $progress['average'];
            }
        }

        return [
            'rows' => $rows,
            'average' => $averages === [] ? null : round(array_sum($averages) / count($averages), 2),
        ];
    }
}
