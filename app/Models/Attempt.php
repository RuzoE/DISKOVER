<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Database\Factories\AttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    /** @use HasFactory<AttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'student_id',
        'number',
        'status',
        'score',
        'max_score',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'number' => 'integer',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Evaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return HasMany<AttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function percentage(): ?float
    {
        if ($this->score === null || ! $this->max_score) {
            return null;
        }

        return round(((float) $this->score / (float) $this->max_score) * 100, 2);
    }

    public function deadlineReached(): bool
    {
        $limit = $this->evaluation->time_limit_minutes;

        return $limit !== null
            && $this->started_at !== null
            && $this->started_at->copy()->addMinutes($limit)->isPast();
    }
}
