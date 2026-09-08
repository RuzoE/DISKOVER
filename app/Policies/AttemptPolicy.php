<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\Evaluation;
use App\Models\User;

class AttemptPolicy
{
    /**
     * Comenzar/continuar un intento: estudiante inscrito en el curso de la
     * asignatura y evaluación abierta. Los límites de intentos y de tiempo se
     * comprueban en AttemptService.
     */
    public function create(User $user, Evaluation $evaluation): bool
    {
        $activity = $evaluation->activity;

        return $activity->isOpenNow()
            && $user->isEnrolledIn($activity->subject->course);
    }

    public function view(User $user, Attempt $attempt): bool
    {
        if ($attempt->student_id === $user->id) {
            return true;
        }

        return $user->hasPermission('activities.manage')
            && $attempt->evaluation->activity->subject->isTaughtBy($user);
    }

    public function update(User $user, Attempt $attempt): bool
    {
        return $attempt->student_id === $user->id && $attempt->status->isOpen();
    }

    public function grade(User $user, Attempt $attempt): bool
    {
        return $user->hasPermission('grades.manage')
            && $attempt->evaluation->activity->subject->isTaughtBy($user);
    }
}
