<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

/**
 * Gestión de inscripciones: coordinación/administración (`enrollments.manage`).
 */
class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enrollments.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('enrollments.manage');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermission('enrollments.manage');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermission('enrollments.manage');
    }
}
