<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

/**
 * Alta/baja/edición de asignaturas: coordinación/administración
 * (`subjects.manage`). Los docentes las consultan a través de
 * `contents.manage` sobre las asignaturas que imparten.
 */
class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('subjects.manage');
    }

    public function view(User $user, Subject $subject): bool
    {
        return $user->hasPermission('subjects.manage') || $subject->isTaughtBy($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('subjects.manage');
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasPermission('subjects.manage');
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasPermission('subjects.manage');
    }
}
