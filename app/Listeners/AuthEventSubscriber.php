<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Registro de eventos de autenticación.
 *
 * En esta fase la traza va al canal de log de la aplicación y se actualiza
 * `users.last_login_at`. La Fase 11 introducirá la tabla de auditoría
 * persistente; este subscriber será su único punto de enganche.
 */
class AuthEventSubscriber
{
    public function handleLogin(Login $event): void
    {
        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

        Log::channel('stack')->info('auth.login', [
            'user_id' => $event->user->getAuthIdentifier(),
            'guard' => $event->guard,
            'ip' => request()->ip(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        Log::channel('stack')->info('auth.logout', [
            'user_id' => $event->user?->getAuthIdentifier(),
            'guard' => $event->guard,
            'ip' => request()->ip(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        Log::channel('stack')->warning('auth.failed', [
            'email' => $event->credentials['email'] ?? null,
            'ip' => request()->ip(),
        ]);
    }

    public function handleLockout(Lockout $event): void
    {
        Log::channel('stack')->warning('auth.lockout', [
            'email' => $event->request->input('email'),
            'ip' => $event->request->ip(),
        ]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        Log::channel('stack')->info('auth.password_reset', [
            'user_id' => $event->user->getAuthIdentifier(),
            'ip' => request()->ip(),
        ]);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
