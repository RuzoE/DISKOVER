<?php

namespace App\Enums;

/**
 * Ciclo de vida compartido por cursos y asignaturas.
 *
 * - Draft:    en preparación, no visible para estudiantes.
 * - Active:   disponible para estudiantes inscritos.
 * - Archived: finalizado; se conserva como histórico.
 */
enum AcademicStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Active => 'Activo',
            self::Archived => 'Archivado',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Active => 'green',
            self::Archived => 'neutral',
        };
    }

    public function isOpenForStudents(): bool
    {
        return $this === self::Active;
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
