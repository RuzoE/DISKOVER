<?php

namespace App\Listeners;

use App\Services\Security\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Eventos de autenticación → registro de auditoría persistente (`audit_logs`)
 * y canal de log de la aplicación. También actualiza `users.last_login_at`.
 */
class AuthEventSubscriber
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handleLogin(Login $event): void
    {
        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->audit->record('auth.login', $event->user, ['guard' => $event->guard], 'Inicio de sesión', $event->user);
        Log::channel('stack')->info('auth.login', ['user_id' => $event->user->getAuthIdentifier()]);
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user !== null) {
            $this->audit->record('auth.logout', $event->user, ['guard' => $event->guard], 'Cierre de sesión', $event->user);
        }
        Log::channel('stack')->info('auth.logout', ['user_id' => $event->user?->getAuthIdentifier()]);
    }

    public function handleFailed(Failed $event): void
    {
        $this->audit->record('auth.failed', null, ['email' => $event->credentials['email'] ?? null], 'Intento de acceso fallido');
        Log::channel('stack')->warning('auth.failed', ['email' => $event->credentials['email'] ?? null]);
    }

    public function handleLockout(Lockout $event): void
    {
        $this->audit->record('auth.lockout', null, ['email' => $event->request->input('email')], 'Bloqueo temporal por intentos');
        Log::channel('stack')->warning('auth.lockout', ['email' => $event->request->input('email')]);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->audit->record('auth.password_reset', $event->user, [], 'Contraseña restablecida', $event->user);
        Log::channel('stack')->info('auth.password_reset', ['user_id' => $event->user->getAuthIdentifier()]);
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
