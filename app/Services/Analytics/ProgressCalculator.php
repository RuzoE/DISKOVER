<?php

namespace App\Services\Analytics;

use App\Enums\AcademicStatus;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Cálculo del progreso de un estudiante (contenidos completados + actividades
 * calificadas) y de su promedio, a nivel de asignatura y de curso.
 *
 * Trabaja solo con lo publicado y las asignaturas activas.
 */
class ProgressCalculator
{
    /**
     * @return array{
     *   percentage: float, items_total: int, items_done: int,
     *   contents_total: int, contents_done: int,
     *   activities_total: int, activities_graded: int,
     *   average: float|null, pending: int, overdue: int
     * }
     */
    public function forSubject(User $student, Subject $subject): array
    {
        $contents = $subject->contents()->where('is_published', true)->get(['id']);
        $activities = $subject->activities()->where('is_published', true)->get();

        $completedContentIds = $student->completedContents()
            ->whereIn('content_id', $contents->pluck('id'))
            ->pluck('content_id');

        $grades = $student->grades()
            ->whereIn('activity_id', $activities->pluck('id'))
            ->get()
            ->keyBy('activity_id');

        return $this->assemble($contents->count(), $completedContentIds->count(), $activities, $grades);
    }

    /**
     * @return array{
     *   percentage: float, items_total: int, items_done: int,
     *   contents_total: int, contents_done: int,
     *   activities_total: int, activities_graded: int,
     *   average: float|null, pending: int, overdue: int,
     *   subjects: array<int, array<string, mixed>>
     * }
     */
    public function forCourse(User $student, Course $course): array
    {
        $subjects = $course->subjects()
            ->where('status', AcademicStatus::Active->value)
            ->with(['contents', 'activities'])
            ->get();

        $perSubject = [];
        $agg = ['ct' => 0, 'cd' => 0, 'at' => 0, 'ag' => 0, 'pending' => 0, 'overdue' => 0];
        $gradePercents = [];

        foreach ($subjects as $subject) {
            $row = $this->forSubject($student, $subject);
            $perSubject[] = ['subject' => $subject, 'progress' => $row];

            $agg['ct'] += $row['contents_total'];
            $agg['cd'] += $row['contents_done'];
            $agg['at'] += $row['activities_total'];
            $agg['ag'] += $row['activities_graded'];
            $agg['pending'] += $row['pending'];
            $agg['overdue'] += $row['overdue'];

            if ($row['average'] !== null) {
                $gradePercents[] = ['weight' => $row['activities_graded'], 'value' => $row['average']];
            }
        }

        $itemsTotal = $agg['ct'] + $agg['at'];
        $itemsDone = $agg['cd'] + $agg['ag'];
        $weightSum = array_sum(array_column($gradePercents, 'weight'));
        $average = $weightSum > 0
            ? round(array_sum(array_map(fn ($g) => $g['value'] * $g['weight'], $gradePercents)) / $weightSum, 2)
            : null;

        return [
            'percentage' => $itemsTotal > 0 ? round($itemsDone / $itemsTotal * 100, 1) : 0.0,
            'items_total' => $itemsTotal,
            'items_done' => $itemsDone,
            'contents_total' => $agg['ct'],
            'contents_done' => $agg['cd'],
            'activities_total' => $agg['at'],
            'activities_graded' => $agg['ag'],
            'average' => $average,
            'pending' => $agg['pending'],
            'overdue' => $agg['overdue'],
            'subjects' => $perSubject,
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @param  Collection<int, Grade>  $gradesByActivity
     * @return array<string, mixed>
     */
    private function assemble(int $contentsTotal, int $contentsDone, Collection $activities, Collection $gradesByActivity): array
    {
        $activitiesTotal = $activities->count();
        $graded = 0;
        $pending = 0;
        $overdue = 0;
        $percents = [];

        foreach ($activities as $activity) {
            $grade = $gradesByActivity->get($activity->id);

            if ($grade !== null) {
                $graded++;
                $max = (float) $activity->max_score;
                $percents[] = $max > 0 ? (float) $grade->score / $max * 100 : 0.0;

                continue;
            }

            $activity->isPastDue() ? $overdue++ : $pending++;
        }

        $itemsTotal = $contentsTotal + $activitiesTotal;
        $itemsDone = $contentsDone + $graded;

        return [
            'percentage' => $itemsTotal > 0 ? round($itemsDone / $itemsTotal * 100, 1) : 0.0,
            'items_total' => $itemsTotal,
            'items_done' => $itemsDone,
            'contents_total' => $contentsTotal,
            'contents_done' => $contentsDone,
            'activities_total' => $activitiesTotal,
            'activities_graded' => $graded,
            'average' => $percents === [] ? null : round(array_sum($percents) / count($percents), 2),
            'pending' => $pending,
            'overdue' => $overdue,
        ];
    }
}
