<?php

namespace App\Enums;

/**
 * Tipos de evento del registro de aprendizaje (`learning_events`).
 *
 * Es un histórico pedagógico de la actividad del estudiante, distinto de la
 * auditoría de seguridad/administración de la Fase 11.
 */
enum LearningEventType: string
{
    case Enrolled = 'enrolled';
    case ContentCompleted = 'content_completed';
    case AttemptSubmitted = 'attempt_submitted';
    case ActivityGraded = 'activity_graded';

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'Inscripción en curso',
            self::ContentCompleted => 'Contenido completado',
            self::AttemptSubmitted => 'Evaluación enviada',
            self::ActivityGraded => 'Actividad calificada',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Enrolled => 'cap',
            self::ContentCompleted => 'book',
            self::AttemptSubmitted => 'clipboard',
            self::ActivityGraded => 'chart',
        };
    }
}
