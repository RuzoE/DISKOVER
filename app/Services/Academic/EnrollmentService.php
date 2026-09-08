<?php

namespace App\Services\Academic;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleSlug;
use App\Events\Academic\StudentEnrolled;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    /**
     * Inscribe a un estudiante en un curso. Idempotente: si ya existe una
     * inscripción, se reactiva en lugar de duplicarla.
     */
    public function enroll(Course $course, User $student, EnrollmentStatus $status = EnrollmentStatus::Active): Enrollment
    {
        $enrollment = $course->enrollments()->firstOrNew(['student_id' => $student->id]);
        $isNew = ! $enrollment->exists;

        $enrollment->fill([
            'status' => $status,
            'enrolled_at' => $enrollment->enrolled_at ?? now(),
        ])->save();

        if ($isNew) {
            $enrollment->setRelation('student', $student);
            $enrollment->setRelation('course', $course);
            StudentEnrolled::dispatch($enrollment);
        }

        return $enrollment;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(Enrollment $enrollment, array $data): Enrollment
    {
        $enrollment->update(['status' => $data['status']]);

        return $enrollment;
    }

    public function remove(Enrollment $enrollment): void
    {
        $enrollment->delete();
    }

    public function assertStudent(User $user): void
    {
        if (! $user->hasRole(RoleSlug::Student)) {
            throw ValidationException::withMessages([
                'student_id' => 'El usuario seleccionado no tiene el rol de estudiante.',
            ]);
        }
    }
}
