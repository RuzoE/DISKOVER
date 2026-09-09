<?php

namespace App\Services\Recommendations\Rules;

use App\DTOs\Recommendations\RecommendationDraft;
use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\RecommendationPriority;
use App\Enums\RecommendationType;
use App\Models\Activity;
use App\Models\User;
use App\Services\Recommendations\Contracts\RecommendationRule;

/**
 * Actividades cuya entrega vence en los próximos días y que el estudiante
 * todavía no tiene calificadas. Prioridad media.
 */
class UpcomingActivitiesRule implements RecommendationRule
{
    private const WINDOW_DAYS = 7;

    public function evaluate(User $student): iterable
    {
        $courseIds = $student->enrollments()
            ->whereIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->pluck('course_id');

        $activities = Activity::query()
            ->where('is_published', true)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addDays(self::WINDOW_DAYS)])
            ->whereHas('subject', fn ($q) => $q->whereIn('course_id', $courseIds)
                ->where('status', AcademicStatus::Active->value))
            ->whereDoesntHave('grades', fn ($q) => $q->where('student_id', $student->id))
            ->with('subject')
            ->orderBy('due_at')
            ->get();

        foreach ($activities as $activity) {
            yield new RecommendationDraft(
                type: RecommendationType::CompletePending,
                priority: RecommendationPriority::Medium,
                title: 'Entrega próxima',
                body: sprintf(
                    '«%s» (%s) vence el %s. Planifica tiempo esta semana para completarla.',
                    $activity->title,
                    $activity->subject->name,
                    $activity->due_at->format('d/m/Y'),
                ),
                signature: 'pending:activity:'.$activity->id,
                reason: ['due_at' => $activity->due_at->toDateString()],
                subjectId: $activity->subject_id,
                activityId: $activity->id,
            );
        }
    }
}
