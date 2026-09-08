<?php

use App\Enums\RoleSlug;

return [

    /*
    |--------------------------------------------------------------------------
    | Control de acceso basado en roles (RBAC)
    |--------------------------------------------------------------------------
    |
    | super_role: rol que omite todas las comprobaciones de permiso (Gate::before).
    | Ver ADR-0003.
    |
    */
    'rbac' => [
        'super_role' => RoleSlug::Admin->value,
    ],

    /*
    |--------------------------------------------------------------------------
    | Usuario administrador inicial (seeder)
    |--------------------------------------------------------------------------
    |
    | Se crea únicamente al ejecutar los seeders. En producción, define estas
    | variables en el entorno y cambia la contraseña tras el primer acceso.
    |
    */
    'seed_admin' => [
        'name' => env('DSLE_ADMIN_NAME', 'Administrador DSLE'),
        'email' => env('DSLE_ADMIN_EMAIL', 'admin@diskover.test'),
        'password' => env('DSLE_ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seguridad de autenticación
    |--------------------------------------------------------------------------
    |
    | Intentos de inicio de sesión permitidos por combinación email + IP antes
    | de aplicar rate limiting (segundos de bloqueo escalado por Laravel).
    |
    */
    'auth' => [
        'max_login_attempts' => (int) env('DSLE_MAX_LOGIN_ATTEMPTS', 5),
    ],

];
