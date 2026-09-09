<?php

namespace App\Models\Concerns;

use App\Services\Security\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra en `audit_logs` las altas, cambios y bajas del modelo. Aplíquese a
 * entidades sensibles (usuarios, roles, cursos, inscripciones, experiencias),
 * no a modelos de alto volumen que ya tienen su propia traza.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => static::writeAudit('created', $model, [
            'new' => static::auditAttributes($model->getAttributes()),
        ]));

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            if ($changes === []) {
                return;
            }

            static::writeAudit('updated', $model, [
                'old' => static::auditAttributes(array_intersect_key($model->getOriginal(), $changes)),
                'new' => static::auditAttributes($changes),
            ]);
        });

        static::deleted(fn (Model $model) => static::writeAudit('deleted', $model, [
            'old' => static::auditAttributes($model->getOriginal()),
        ]));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected static function writeAudit(string $action, Model $model, array $properties): void
    {
        app(AuditLogger::class)->record(
            strtolower(class_basename($model)).'.'.$action,
            $model,
            $properties,
        );
    }

    /**
     * Quita timestamps y campos ocultos antes de guardar el diff.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function auditAttributes(array $attributes): array
    {
        $hidden = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];

        return array_diff_key($attributes, array_flip($hidden));
    }
}
