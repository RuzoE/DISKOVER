<?php

namespace App\Models;

use App\Enums\AcademicStatus;
use App\Enums\EnrollmentStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'status' => AcademicStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    /**
     * @return HasMany<Subject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Estudiantes inscritos (cualquier estado).
     *
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'course_id', 'student_id')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }

    public function isOpenForStudents(): bool
    {
        return $this->status->isOpenForStudents();
    }

    public function hasActiveStudent(User $user): bool
    {
        return $this->enrollments()
            ->where('student_id', $user->id)
            ->whereIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->exists();
    }
}
