<?php

namespace App\Models;

use App\Enums\AcademicStatus;
use App\Enums\ImmersiveProvider;
use App\Models\Concerns\Auditable;
use Database\Factories\ImmersiveExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ImmersiveExperience extends Model
{
    /** @use HasFactory<ImmersiveExperienceFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'provider',
        'launch_url',
        'config',
        'max_score',
        'status',
        'activity_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'provider' => ImmersiveProvider::class,
            'config' => 'array',
            'max_score' => 'decimal:2',
            'status' => AcademicStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $experience): void {
            if (blank($experience->slug) && filled($experience->title)) {
                $experience->slug = Str::slug($experience->title).'-'.Str::lower(Str::random(4));
            }
        });
    }

    /**
     * @return BelongsToMany<Subject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'experience_subject')->withPivot('is_required');
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ImmersiveSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ImmersiveSession::class);
    }

    public function isActive(): bool
    {
        return $this->status === AcademicStatus::Active;
    }
}
