<?php

namespace App\Services\Recommendations\Rules;

use App\DTOs\Recommendations\RecommendationDraft;
use App\Enums\AcademicStatus;
use App\Enums\ActivityType;
use App\Enums\AttemptStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\RecommendationPriority;
use App\Enums\RecommendationType;
use App\Models\Activity;
use App\Models\User;
use App\Services\Recommendations\Contracts\RecommendationRule;

/**
 * Evaluaciones que el estudiante aprobó por debajo del umbral (o de su nota de
 * aprobado) y en las que aún le quedan intentos disponibles. Prioridad media.
 */
class RetryEvaluationRule implements RecommendationRule
{
    public function evaluate(User $student): iterable
    {
        $threshold = (float) config('dsle.analytics.pass_threshold', 60);

        $courseIds = $student->enrollments()
            ->whereIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->pluck('course_id');

        $activities = Activity::query()
            ->where('type', ActivityType::Quiz->value)
            ->where('is_published', true)
            ->whereHas('subject', fn ($q) => $q->whereIn('course_id', $courseIds)
                ->where('status', AcademicStatus::Active->value))
            ->with(['subject', 'evaluation', 'grades' => fn ($q) => $q->where('student_id', $student->id)])
            ->get();

        foreach ($activities as $activity) {
            $evaluation = $activity->evaluation;
            $grade = $activity->grades->first();

            if ($evaluation === null || $grade === null) {
                continue;
            }

            $percent = (float) $activity->max_score > 0
                ? round((float) $grade->score / (float) $activity->max_score * 100, 2)
                : 0.0;

            $passMark = $evaluation->pass_score !== null ? (float) $evaluation->pass_score : $threshold;

            if ($percent >= $passMark) {
                continue;
            }

            $used = $evaluation->attempts()
                ->where('student_id', $student->id)
                ->whereIn('status', [AttemptStatus::Submitted->value, AttemptStatus::Graded->value])
                ->count();

            $remaining = max(0, (int) $evaluation->max_attempts - $used);

            if ($remaining < 1) {
                continue;
            }

            yield new RecommendationDraft(
                type: RecommendationType::RetryEvaluation,
                priority: RecommendationPriority::Medium,
                title: 'Vuelve a intentar «'.$activity->title.'»',
                body: sprintf(
                    'Obtuviste un %s%% en «%s» (%s), por debajo del %s%%. Te queda%s %d intento%s: repasa y vuelve a intentarlo.',
                    rtrim(rtrim(number_format($percent, 1), '0'), '.'),
                    $activity->title,
                    $activity->subject->name,
                    rtrim(rtrim(number_format($passMark, 1), '0'), '.'),
                    $remaining === 1 ? '' : 'n',
                    $remaining,
                    $remaining === 1 ? '' : 's',
                ),
                signature: 'retry:activity:'.$activity->id,
                reason: ['percent' => $percent, 'pass_mark' => $passMark, 'attempts_left' => $remaining],
                subjectId: $activity->subject_id,
                activityId: $activity->id,
            );
        }
    }
}
