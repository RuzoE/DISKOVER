<?php

namespace App\Enums;

/**
 * Estado de un intento de evaluación.
 *
 * - InProgress: el estudiante lo está resolviendo.
 * - Submitted:  enviado; corregidas las preguntas cerradas. Si hay preguntas
 *               abiertas, queda a la espera de revisión del docente.
 * - Graded:     revisión completa; la nota final está consolidada.
 */
enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Graded = 'graded';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'En curso',
            self::Submitted => 'Enviado',
            self::Graded => 'Calificado',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::InProgress => 'amber',
            self::Submitted => 'blue',
            self::Graded => 'green',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::InProgress;
    }
}
