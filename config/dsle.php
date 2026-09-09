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

    /*
    |--------------------------------------------------------------------------
    | Inteligencia artificial (asistente educativo)
    |--------------------------------------------------------------------------
    |
    | La IA está aislada tras App\Services\AI\Contracts\AiProvider. Si no hay
    | proveedor configurado (o falta la clave), se usa StubAiProvider, que
    | responde sin red a partir del contexto académico. Ver ADR-0010.
    |
    | provider:  null | 'openai'  (compatible con la API de chat de OpenAI:
    |            OpenAI, Groq, OpenRouter, Ollama…). Las claves van SOLO en .env.
    |
    */
    'ai' => [
        'provider' => env('DSLE_AI_PROVIDER') ?: null,
        'base_url' => rtrim((string) env('DSLE_AI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'api_key' => env('DSLE_AI_API_KEY'),
        'model' => env('DSLE_AI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('DSLE_AI_TIMEOUT', 30),
        'temperature' => (float) env('DSLE_AI_TEMPERATURE', 0.4),
        'max_history' => (int) env('DSLE_AI_MAX_HISTORY', 12),
        'rate_limit_per_minute' => (int) env('DSLE_AI_RATE_LIMIT', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Experiencias inmersivas (Fase 9)
    |--------------------------------------------------------------------------
    |
    | Laravel registra experiencias y sesiones; Unity ejecuta la experiencia y
    | usa la API /api/v1/immersive con el token de la sesión. Ver ADR-0012.
    |
    | session_ttl_minutes: tras este tiempo, una sesión «started» se considera
    |   caducada y no admite resultados.
    | api_rate_limit_per_minute: límite de la API inmersiva por token/IP.
    |
    */
    'immersive' => [
        'session_ttl_minutes' => (int) env('DSLE_IMMERSIVE_SESSION_TTL', 360),
        'api_rate_limit_per_minute' => (int) env('DSLE_IMMERSIVE_API_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditoría, seguridad y backups (Fase 11)
    |--------------------------------------------------------------------------
    */
    'audit' => [
        // Claves que nunca deben guardarse en el registro de auditoría.
        'redact' => ['password', 'password_confirmation', 'remember_token', 'token',
            'api_key', 'secret', 'launch_token', 'current_password'],
    ],

    'security' => [
        // Cabeceras de seguridad HTTP añadidas a todas las respuestas web.
        'hsts_max_age' => (int) env('DSLE_HSTS_MAX_AGE', 31536000),
    ],

    'backups' => [
        'path' => storage_path('app/backups'),
        'keep' => (int) env('DSLE_BACKUP_KEEP', 7),
        'mysqldump_path' => env('DSLE_MYSQLDUMP_PATH', 'mysqldump'),
    ],

];
