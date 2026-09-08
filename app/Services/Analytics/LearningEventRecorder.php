<?php

namespace App\Services\Analytics;

use App\Enums\LearningEventType;
use App\Models\LearningEvent;
use App\Models\User;

/**
 * Punto único de escritura del registro de aprendizaje (`learning_events`).
 * Lo alimenta LearningEventSubscriber a partir de los eventos de dominio.
 */
class LearningEventRecorder
{
    /**
     * @param  array{course_id?: int|null, subject_id?: int|null, activity_id?: int|null, payload?: array<string, mixed>}  $context
     */
    public function record(User $user, LearningEventType $type, string $description, array $context = []): LearningEvent
    {
        return LearningEvent::create([
            'user_id' => $user->id,
            'course_id' => $context['course_id'] ?? null,
            'subject_id' => $context['subject_id'] ?? null,
            'activity_id' => $context['activity_id'] ?? null,
            'type' => $type,
            'description' => $description,
            'payload' => $context['payload'] ?? null,
            'occurred_at' => now(),
        ]);
    }
}
