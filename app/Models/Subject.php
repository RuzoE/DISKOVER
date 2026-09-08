<?php

namespace App\Models;

use App\Enums\AcademicStatus;
use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'teacher_id',
        'code',
        'name',
        'description',
        'position',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => AcademicStatus::class,
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return HasMany<Content, $this>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderBy('position')->orderBy('id');
    }

    public function isTaughtBy(User $user): bool
    {
        return $this->teacher_id !== null && $this->teacher_id === $user->id;
    }
}
