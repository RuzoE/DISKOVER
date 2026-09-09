<?php

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'type',
        'title',
        'description',
        'instructions',
        'max_score',
        'opens_at',
        'due_at',
        'position',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'max_score' => 'decimal:2',
            'opens_at' => 'datetime',
            'due_at' => 'datetime',
            'position' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return HasOne<Evaluation, $this>
     */
    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    /**
     * @return HasOne<ImmersiveExperience, $this>
     */
    public function immersiveExperience(): HasOne
    {
        return $this->hasOne(ImmersiveExperience::class);
    }

    /**
     * @return HasMany<Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function isQuiz(): bool
    {
        return $this->type === ActivityType::Quiz;
    }

    public function isOpenNow(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $now = now();

        return (! $this->opens_at || $this->opens_at->lte($now))
            && (! $this->due_at || $this->due_at->gte($now));
    }

    public function isPastDue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }
}
