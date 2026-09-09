<?php

namespace App\Services\Academic;

use App\Enums\AttemptStatus;
use App\Enums\GradeSource;
use App\Events\Academic\ActivityGraded;
use App\Models\Activity;
use App\Models\Attempt;
use App\Models\Grade;
use App\Models\User;

class GradeService
{
    /**
     * Calificación manual de un trabajo (o ajuste sobre una evaluación).
     */
    public function setManual(Activity $activity, User $student, float $score, ?string $feedback, User $grader): Grade
    {
        $grade = Grade::updateOrCreate(
            ['activity_id' => $activity->id, 'student_id' => $student->id],
            [
                'score' => round($score, 2),
                'feedback' => $feedback,
                'source' => GradeSource::Manual,
                'graded_by' => $grader->id,
                'graded_at' => now(),
            ],
        );

        $grade->setRelation('activity', $activity);
        $grade->setRelation('student', $student);
        ActivityGraded::dispatch($grade);

        return $grade;
    }

    /**
     * Calificación derivada del resultado de una experiencia inmersiva. Escala
     * la puntuación al máximo de la actividad. No pisa una nota manual.
     */
    public function setFromImmersive(Activity $activity, User $student, float $score, float $sourceMax): ?Grade
    {
        $existing = Grade::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && $existing->source === GradeSource::Manual) {
            return $existing;
        }

        $scaled = $sourceMax > 0
            ? round($score / $sourceMax * (float) $activity->max_score, 2)
            : 0.0;

        $grade = Grade::updateOrCreate(
            ['activity_id' => $activity->id, 'student_id' => $student->id],
            [
                'score' => $scaled,
                'source' => GradeSource::Immersive,
                'graded_by' => null,
                'graded_at' => now(),
            ],
        );

        $grade->setRelation('activity', $activity);
        $grade->setRelation('student', $student);
        ActivityGraded::dispatch($grade);

        return $grade;
    }

    /**
     * Deriva (o actualiza) la calificación de una evaluación a partir del
     * mejor intento calificado del estudiante. Se llama tras cada envío o
     * revisión. No pisa una calificación manual o inmersiva existente.
     */
    public function syncFromBestAttempt(Activity $activity, User $student): ?Grade
    {
        $existing = Grade::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && in_array($existing->source, [GradeSource::Manual, GradeSource::Immersive], true)) {
            return $existing;
        }

        $best = Attempt::query()
            ->whereHas('evaluation', fn ($q) => $q->where('activity_id', $activity->id))
            ->where('student_id', $student->id)
            ->where('status', AttemptStatus::Graded->value)
            ->orderByDesc('score')
            ->first();

        if ($best === null) {
            return $existing;
        }

        $max = (float) $best->max_score;
        $scaled = $max > 0
            ? round(((float) $best->score / $max) * (float) $activity->max_score, 2)
            : 0.0;

        $grade = Grade::updateOrCreate(
            ['activity_id' => $activity->id, 'student_id' => $student->id],
            [
                'attempt_id' => $best->id,
                'score' => $scaled,
                'source' => GradeSource::Auto,
                'graded_by' => null,
                'graded_at' => now(),
            ],
        );

        $grade->setRelation('activity', $activity);
        $grade->setRelation('student', $student);
        ActivityGraded::dispatch($grade);

        return $grade;
    }
}
