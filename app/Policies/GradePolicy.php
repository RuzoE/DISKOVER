<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    /**
     * Ver/gestionar el cuaderno de calificaciones de una actividad.
     */
    public function manage(User $user, Activity $activity): bool
    {
        return $user->hasPermission('grades.manage') && $activity->subject->isTaughtBy($user);
    }

    public function view(User $user, Grade $grade): bool
    {
        if ($grade->student_id === $user->id) {
            return true;
        }

        return $user->hasPermission('grades.manage')
            && $grade->activity->subject->isTaughtBy($user);
    }
}
