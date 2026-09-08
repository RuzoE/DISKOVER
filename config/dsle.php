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

    /*
    |--------------------------------------------------------------------------
    | Learning Analytics
    |--------------------------------------------------------------------------
    |
    | pass_threshold:    porcentaje por debajo del cual una calificación se
    |                    considera "no superada" (para señalar dificultades).
    | at_risk_threshold: promedio (%) por debajo del cual un estudiante se marca
    |                    como "en riesgo" en un curso.
    | weak_question_rate: tasa de acierto (0-1) por debajo de la cual una
    |                     pregunta se considera "difícil".
    |
    */
    'analytics' => [
        'pass_threshold' => (float) env('DSLE_PASS_THRESHOLD', 60),
        'at_risk_threshold' => (float) env('DSLE_AT_RISK_THRESHOLD', 60),
        'weak_question_rate' => (float) env('DSLE_WEAK_QUESTION_RATE', 0.5),
    ],

];
