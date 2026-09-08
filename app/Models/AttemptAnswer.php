<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_option_ids',
        'text_answer',
        'is_correct',
        'score_awarded',
    ];

    protected function casts(): array
    {
        return [
            'selected_option_ids' => 'array',
            'is_correct' => 'boolean',
            'score_awarded' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Attempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
