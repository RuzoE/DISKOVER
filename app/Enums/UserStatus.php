<?php

namespace App\Enums;

/**
 * Estado de una cuenta de usuario dentro de DSLE.
 *
 * - Active:    puede iniciar sesión y operar con normalidad.
 * - Inactive:  cuenta deshabilitada temporalmente (p. ej. estudiante egresado).
 * - Suspended: bloqueada por una acción administrativa o de seguridad.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Inactive => 'Inactivo',
            self::Suspended => 'Suspendido',
        };
    }

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }

    /**
     * @return array<string, string> [value => label]
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $status) => $carry + [$status->value => $status->label()],
            [],
        );
    }
}
