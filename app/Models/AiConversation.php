<?php

namespace App\Models;

use Database\Factories\AiConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    /** @use HasFactory<AiConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'context_type',
        'context_id',
        'provider',
        'model',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
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
     * @return HasMany<AiMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('id');
    }

    public function contextLabel(): string
    {
        return match ($this->context_type) {
            'course' => 'Curso: '.(Course::whereKey($this->context_id)->value('name') ?? '—'),
            'subject' => 'Asignatura: '.(Subject::whereKey($this->context_id)->value('name') ?? '—'),
            default => 'General',
        };
    }
}
