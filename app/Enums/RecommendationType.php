<?php

namespace App\Enums;

/**
 * Tipo de recomendación generada por el motor de reglas (Fase 8).
 * NO tiene relación con la IA generativa (Fase 7).
 */
enum RecommendationType: string
{
    case OverdueAlert = 'overdue_alert';
    case CompletePending = 'complete_pending';
    case FocusSubject = 'focus_subject';
    case RetryEvaluation = 'retry_evaluation';
    case ReviewContent = 'review_content';
    case Positive = 'positive';

    public function label(): string
    {
        return match ($this) {
            self::OverdueAlert => 'Actividad vencida',
            self::CompletePending => 'Entrega próxima',
            self::FocusSubject => 'Reforzar asignatura',
            self::RetryEvaluation => 'Reintentar evaluación',
            self::ReviewContent => 'Repasar contenido',
            self::Positive => 'Buen ritmo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OverdueAlert => 'clipboard',
            self::CompletePending => 'clipboard',
            self::FocusSubject => 'stack',
            self::RetryEvaluation => 'chart',
            self::ReviewContent => 'book',
            self::Positive => 'sparkles',
        };
    }
}
