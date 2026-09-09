<?php

namespace App\Models;

use App\Enums\ImmersiveSessionStatus;
use Database\Factories\ImmersiveSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmersiveSession extends Model
{
    /** @use HasFactory<ImmersiveSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'immersive_experience_id',
        'student_id',
        'launch_token',
        'status',
        'score',
        'max_score',
        'payload',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ImmersiveSessionStatus::class,
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'payload' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ImmersiveExperience, $this>
     */
    public function experience(): BelongsTo
    {
        return $this->belongsTo(ImmersiveExperience::class, 'immersive_experience_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function isExpired(): bool
    {
        $ttl = (int) config('dsle.immersive.session_ttl_minutes', 360);

        return $this->status === ImmersiveSessionStatus::Started
            && $this->started_at->copy()->addMinutes($ttl)->isPast();
    }

    public function percentage(): ?float
    {
        if ($this->score === null || ! $this->max_score) {
            return null;
        }

        return round((float) $this->score / (float) $this->max_score * 100, 2);
    }
}
