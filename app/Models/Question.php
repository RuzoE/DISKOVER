<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'type',
        'statement',
        'score',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'score' => 'decimal:2',
            'position' => 'integer',
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
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return array<int, int> ids de las opciones correctas
     */
    public function correctOptionIds(): array
    {
        return $this->options->where('is_correct', true)->pluck('id')->all();
    }
}
