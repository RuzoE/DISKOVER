<?php

namespace App\Enums;

/**
 * Roles base del sistema (creados por el seeder y marcados como is_system).
 *
 * Estos slugs son estables y pueden referenciarse desde código (middleware,
 * policies, redirecciones por rol). Los roles adicionales que cree un
 * administrador NO se representan aquí.
 */
enum RoleSlug: string
{
    case Admin = 'admin';
    case Coordinator = 'coordinator';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Coordinator => 'Coordinación',
            self::Teacher => 'Docente',
            self::Student => 'Estudiante',
        };
    }

    /**
     * Ruta a la que se envía al usuario tras iniciar sesión.
     * En la Fase 5 cada rol tendrá su propio dashboard; por ahora
     * todos comparten el panel genérico.
     */
    public function homeRoute(): string
    {
        return 'dashboard';
    }
}
