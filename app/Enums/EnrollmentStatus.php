<?php

namespace App\Enums;

/**
 * Estado de la inscripción de un estudiante en un curso.
 *
 * - Active:    inscripción vigente.
 * - Withdrawn: el estudiante se dio de baja o fue retirado.
 * - Completed: el estudiante finalizó el curso.
 */
enum EnrollmentStatus: string
{
    case Active = 'active';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Withdrawn => 'Retirada',
            self::Completed => 'Completada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Withdrawn => 'red',
            self::Completed => 'blue',
        };
    }

    public function grantsAccess(): bool
    {
        return $this === self::Active || $this === self::Completed;
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
