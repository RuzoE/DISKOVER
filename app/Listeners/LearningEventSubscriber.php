<?php

namespace App\Listeners;

use App\Enums\LearningEventType;
use App\Events\Academic\ActivityGraded;
use App\Events\Academic\AttemptSubmitted;
use App\Events\Academic\ContentCompleted;
use App\Events\Academic\StudentEnrolled;
use App\Services\Analytics\LearningEventRecorder;
use Illuminate\Events\Dispatcher;

/**
 * Traduce los eventos de dominio académico en filas del registro de aprendizaje.
 */
class LearningEventSubscriber
{
    public function __construct(private readonly LearningEventRecorder $recorder) {}

    public function onStudentEnrolled(StudentEnrolled $event): void
    {
        $enrollment = $event->enrollment;

        $this->recorder->record(
            $enrollment->student,
            LearningEventType::Enrolled,
            "Inscripción en el curso «{$enrollment->course->name}».",
            ['course_id' => $enrollment->course_id, 'payload' => ['status' => $enrollment->status->value]],
        );
    }

    public function onContentCompleted(ContentCompleted $event): void
    {
        $content = $event->content;

        $this->recorder->record(
            $event->student,
            LearningEventType::ContentCompleted,
            "Contenido completado: «{$content->title}».",
            [
                'course_id' => $content->subject->course_id,
                'subject_id' => $content->subject_id,
                'payload' => ['content_id' => $content->id],
            ],
        );
    }

    public function onAttemptSubmitted(AttemptSubmitted $event): void
    {
        $attempt = $event->attempt;
        $activity = $attempt->evaluation->activity;

        $this->recorder->record(
            $attempt->student,
            LearningEventType::AttemptSubmitted,
            "Evaluación enviada: «{$activity->title}» (intento #{$attempt->number}).",
            [
                'course_id' => $activity->subject->course_id,
                'subject_id' => $activity->subject_id,
                'activity_id' => $activity->id,
                'payload' => ['attempt_id' => $attempt->id, 'score' => $attempt->score, 'max_score' => $attempt->max_score],
            ],
        );
    }

    public function onActivityGraded(ActivityGraded $event): void
    {
        $grade = $event->grade;
        $activity = $grade->activity;

        $this->recorder->record(
            $grade->student,
            LearningEventType::ActivityGraded,
            "Calificación registrada en «{$activity->title}»: {$grade->score}/{$activity->max_score}.",
            [
                'course_id' => $activity->subject->course_id,
                'subject_id' => $activity->subject_id,
                'activity_id' => $activity->id,
                'payload' => ['score' => $grade->score, 'source' => $grade->source->value],
            ],
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            StudentEnrolled::class => 'onStudentEnrolled',
            ContentCompleted::class => 'onContentCompleted',
            AttemptSubmitted::class => 'onAttemptSubmitted',
            ActivityGraded::class => 'onActivityGraded',
        ];
    }
}
