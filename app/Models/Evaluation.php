<?php

namespace App\Models;

use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'time_limit_minutes',
        'max_attempts',
        'shuffle_questions',
        'pass_score',
    ];

    protected function casts(): array
    {
        return [
            'time_limit_minutes' => 'integer',
            'max_attempts' => 'integer',
            'shuffle_questions' => 'boolean',
            'pass_score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Attempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function totalScore(): float
    {
        return (float) $this->questions()->sum('score');
    }

    public function hasOpenQuestions(): bool
    {
        return $this->questions()->where('type', 'open')->exists();
    }
}
