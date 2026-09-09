<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro de auditoría: append-only. La aplicación sólo escribe (vía
 * AuditLogger) y lee; nunca actualiza ni borra filas.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actorName(): string
    {
        return $this->user?->name ?? 'Sistema / anónimo';
    }

    public function targetLabel(): ?string
    {
        if ($this->auditable_type === null) {
            return null;
        }

        return class_basename($this->auditable_type).' #'.$this->auditable_id;
    }
}
