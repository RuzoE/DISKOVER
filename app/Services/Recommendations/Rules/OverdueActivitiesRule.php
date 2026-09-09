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
 * Actividades publicadas, vencidas y sin calificar en cursos activos del
 * estudiante. Prioridad alta.
 */
class OverdueActivitiesRule implements RecommendationRule
{
    public function evaluate(User $student): iterable
    {
        $courseIds = $student->enrollments()
            ->whereIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->pluck('course_id');

        $activities = Activity::query()
            ->where('is_published', true)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereHas('subject', fn ($q) => $q->whereIn('course_id', $courseIds)
                ->where('status', AcademicStatus::Active->value))
            ->whereDoesntHave('grades', fn ($q) => $q->where('student_id', $student->id))
            ->with('subject')
            ->orderBy('due_at')
            ->get();

        foreach ($activities as $activity) {
            yield new RecommendationDraft(
                type: RecommendationType::OverdueAlert,
                priority: RecommendationPriority::High,
                title: 'Actividad vencida sin entregar',
                body: sprintf(
                    '«%s» (%s) venció el %s y aún no tiene calificación. Revísala con el docente cuanto antes.',
                    $activity->title,
                    $activity->subject->name,
                    $activity->due_at->format('d/m/Y'),
                ),
                signature: 'overdue:activity:'.$activity->id,
                reason: ['due_at' => $activity->due_at->toDateString()],
                subjectId: $activity->subject_id,
                activityId: $activity->id,
            );
        }
    }
}
