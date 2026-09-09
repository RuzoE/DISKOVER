<?php

namespace App\Enums;

/**
 * Origen de una calificación.
 *
 * - Manual:    introducida por el docente (trabajos, ajuste de evaluaciones).
 * - Auto:      calculada a partir del mejor intento de una evaluación.
 * - Immersive: derivada del resultado de una experiencia inmersiva (Fase 9).
 */
enum GradeSource: string
{
    case Manual = 'manual';
    case Auto = 'auto';
    case Immersive = 'immersive';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Auto => 'Automática',
            self::Immersive => 'Experiencia inmersiva',
        };
    }
}
