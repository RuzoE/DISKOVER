<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\Subject;
use App\Models\User;

/**
 * Actividades y evaluaciones: las gestiona el docente titular de la asignatura
 * (`activities.manage` + titularidad). Administración pasa por Gate::before.
 */
class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('activities.manage');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasPermission('activities.manage') && $activity->subject->isTaughtBy($user);
    }

    public function create(User $user, Subject $subject): bool
    {
        return $user->hasPermission('activities.manage') && $subject->isTaughtBy($user);
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->hasPermission('activities.manage') && $activity->subject->isTaughtBy($user);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->hasPermission('activities.manage') && $activity->subject->isTaughtBy($user);
    }
}
