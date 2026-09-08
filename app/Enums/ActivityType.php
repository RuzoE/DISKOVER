<?php

namespace App\Enums;

/**
 * Tipo de actividad evaluable de una asignatura.
 *
 * - Task: entrega/trabajo que el docente califica manualmente.
 * - Quiz: evaluación en línea con preguntas; se corrige automáticamente
 *   (salvo las preguntas abiertas, que revisa el docente).
 */
enum ActivityType: string
{
    case Task = 'task';
    case Quiz = 'quiz';

    public function label(): string
    {
        return match ($this) {
            self::Task => 'Trabajo',
            self::Quiz => 'Evaluación',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
