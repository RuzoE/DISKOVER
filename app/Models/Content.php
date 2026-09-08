<?php

namespace App\Models;

use App\Enums\ContentType;
use Database\Factories\ContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Content extends Model
{
    /** @use HasFactory<ContentFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'created_by',
        'title',
        'type',
        'body',
        'url',
        'position',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContentType::class,
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
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Estudiantes que han marcado este contenido como completado.
     *
     * @return BelongsToMany<User, $this>
     */
    public function completedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'content_user')
            ->withPivot('completed_at');
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->completedBy()->whereKey($user->id)->exists();
    }
}
