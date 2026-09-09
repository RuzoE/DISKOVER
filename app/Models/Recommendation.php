<?php

namespace App\Models;

use App\Enums\RecommendationPriority;
use App\Enums\RecommendationStatus;
use App\Enums\RecommendationType;
use Database\Factories\RecommendationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    /** @use HasFactory<RecommendationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'priority',
        'title',
        'body',
        'reason',
        'subject_id',
        'activity_id',
        'content_id',
        'status',
        'signature',
        'generated_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => RecommendationType::class,
            'priority' => RecommendationPriority::class,
            'status' => RecommendationStatus::class,
            'reason' => 'array',
            'generated_at' => 'datetime',
            'responded_at' => 'datetime',
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

    /**
     * @return BelongsTo<Content, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * Enlace de acción sugerido según el tipo (a dónde debería ir el estudiante).
     */
    public function actionUrl(): ?string
    {
        return match (true) {
            $this->activity_id !== null => route('student.activities.show', $this->activity_id),
            $this->subject_id !== null => route('student.subjects.show', $this->subject_id),
            default => null,
        };
    }
}
