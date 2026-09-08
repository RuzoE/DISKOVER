<?php

namespace App\Enums;

/**
 * Origen de una calificación.
 *
 * - Manual: introducida por el docente (trabajos, ajuste de evaluaciones).
 * - Auto:   calculada a partir del mejor intento de una evaluación.
 */
enum GradeSource: string
{
    case Manual = 'manual';
    case Auto = 'auto';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Auto => 'Automática',
        };
    }
}
