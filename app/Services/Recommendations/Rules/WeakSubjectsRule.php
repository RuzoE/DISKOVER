<?php

namespace App\Services\Recommendations\Rules;

use App\DTOs\Recommendations\RecommendationDraft;
use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\RecommendationPriority;
use App\Enums\RecommendationType;
use App\Models\User;
use App\Services\Analytics\ProgressCalculator;
use App\Services\Recommendations\Contracts\RecommendationRule;

/**
 * Asignaturas activas cuyo promedio del estudiante está por debajo del umbral
 * de aprobado. Prioridad alta.
 */
class WeakSubjectsRule implements RecommendationRule
{
    public function __construct(private readonly ProgressCalculator $progress) {}

    public function evaluate(User $student): iterable
    {
        $threshold = (float) config('dsle.analytics.pass_threshold', 60);

        $courses = $student->enrolledCourses()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->get();

        foreach ($courses as $course) {
            foreach ($course->subjects()->where('status', AcademicStatus::Active->value)->get() as $subject) {
                $row = $this->progress->forSubject($student, $subject);

                if ($row['average'] === null || $row['average'] >= $threshold) {
                    continue;
                }

                yield new RecommendationDraft(
                    type: RecommendationType::FocusSubject,
                    priority: RecommendationPriority::High,
                    title: 'Refuerza '.$subject->name,
                    body: sprintf(
                        'Tu promedio en «%s» es del %s%%, por debajo del %s%%. Repasa sus contenidos y '
                        .'repite las actividades donde perdiste puntos.',
                        $subject->name,
                        rtrim(rtrim(number_format($row['average'], 1), '0'), '.'),
                        rtrim(rtrim(number_format($threshold, 1), '0'), '.'),
                    ),
                    signature: 'focus:subject:'.$subject->id,
                    reason: ['average' => $row['average'], 'threshold' => $threshold],
                    subjectId: $subject->id,
                );
            }
        }
    }
}
