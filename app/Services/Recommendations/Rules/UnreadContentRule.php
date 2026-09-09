<?php

namespace App\Services\Recommendations\Rules;

use App\DTOs\Recommendations\RecommendationDraft;
use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\RecommendationPriority;
use App\Enums\RecommendationType;
use App\Models\Content;
use App\Models\User;
use App\Services\Analytics\ProgressCalculator;
use App\Services\Recommendations\Contracts\RecommendationRule;

/**
 * Contenidos publicados sin completar en asignaturas donde el estudiante va
 * flojo (o aún sin nota). Prioridad baja. Limitado para no abrumar.
 */
class UnreadContentRule implements RecommendationRule
{
    private const MAX = 5;

    public function __construct(private readonly ProgressCalculator $progress) {}

    public function evaluate(User $student): iterable
    {
        $threshold = (float) config('dsle.analytics.pass_threshold', 60);
        $completedIds = $student->completedContents()->pluck('content_id')->all();
        $emitted = 0;

        $courses = $student->enrolledCourses()
            ->wherePivotIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->get();

        foreach ($courses as $course) {
            foreach ($course->subjects()->where('status', AcademicStatus::Active->value)->get() as $subject) {
                $average = $this->progress->forSubject($student, $subject)['average'];

                if ($average !== null && $average >= $threshold) {
                    continue; // va bien en esta asignatura
                }

                $contents = Content::query()
                    ->where('subject_id', $subject->id)
                    ->where('is_published', true)
                    ->whereNotIn('id', $completedIds)
                    ->orderBy('position')
                    ->get();

                foreach ($contents as $content) {
                    if ($emitted >= self::MAX) {
                        return;
                    }

                    $emitted++;

                    yield new RecommendationDraft(
                        type: RecommendationType::ReviewContent,
                        priority: RecommendationPriority::Low,
                        title: 'Repasa «'.$content->title.'»',
                        body: sprintf(
                            'Aún no has marcado como completado «%s» de «%s». Repásalo para afianzar la asignatura.',
                            $content->title,
                            $subject->name,
                        ),
                        signature: 'review:content:'.$content->id,
                        reason: ['subject_average' => $average],
                        subjectId: $subject->id,
                        contentId: $content->id,
                    );
                }
            }
        }
    }
}
