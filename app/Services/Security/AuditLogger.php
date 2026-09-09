<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Único punto de escritura del registro de auditoría (`audit_logs`). Redacta
 * las claves sensibles antes de persistir (contraseñas, tokens…).
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        string $event,
        ?Model $auditable = null,
        array $properties = [],
        ?string $description = null,
        ?User $actor = null,
    ): AuditLog {
        $actor ??= auth()->user();
        $request = request();

        return AuditLog::create([
            'user_id' => $actor?->getKey(),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'properties' => $this->redact($properties) ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 390, ''),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function redact(array $data): array
    {
        $keys = array_map('strtolower', (array) config('dsle.audit.redact', []));

        array_walk_recursive($data, function (&$value, $key) use ($keys): void {
            $k = strtolower((string) $key);
            foreach ($keys as $needle) {
                if ($k === $needle || str_contains($k, 'token') || str_contains($k, 'password')) {
                    $value = '••••';

                    return;
                }
                if (str_contains($k, $needle)) {
                    $value = '••••';

                    return;
                }
            }
        });

        return $data;
    }
}
