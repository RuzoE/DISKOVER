<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EnrollmentStatus;
use App\Enums\UserStatus;
use App\Models\Concerns\HasRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Inscripciones del usuario como estudiante.
     *
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    /**
     * Cursos en los que el usuario está inscrito como estudiante.
     *
     * @return BelongsToMany<Course, $this>
     */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments', 'student_id', 'course_id')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }

    /**
     * Asignaturas que el usuario imparte como docente.
     *
     * @return HasMany<Subject, $this>
     */
    public function subjectsTeaching(): HasMany
    {
        return $this->hasMany(Subject::class, 'teacher_id');
    }

    public function isEnrolledIn(Course $course): bool
    {
        return $this->enrollments()
            ->where('course_id', $course->id)
            ->whereIn('status', [EnrollmentStatus::Active->value, EnrollmentStatus::Completed->value])
            ->exists();
    }

    /**
     * Intentos de evaluación realizados por el usuario como estudiante.
     *
     * @return HasMany<Attempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class, 'student_id');
    }

    /**
     * Calificaciones del usuario como estudiante.
     *
     * @return HasMany<Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'student_id');
    }
}
