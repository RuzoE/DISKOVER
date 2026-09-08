<?php

namespace App\Models;

use App\Enums\LearningEventType;
use Database\Factories\LearningEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningEvent extends Model
{
    /** @use HasFactory<LearningEventFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'subject_id',
        'activity_id',
        'type',
        'description',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => LearningEventType::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
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
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
