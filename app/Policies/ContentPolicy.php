<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\Subject;
use App\Models\User;

/**
 * Contenidos de una asignatura. Los gestiona el docente que la imparte
 * (`contents.manage` + titularidad de la asignatura). Administración pasa
 * por Gate::before.
 */
class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contents.manage');
    }

    public function view(User $user, Content $content): bool
    {
        return $user->hasPermission('contents.manage')
            && ($content->subject->isTaughtBy($user) || $user->hasPermission('subjects.manage'));
    }

    public function create(User $user, Subject $subject): bool
    {
        return $user->hasPermission('contents.manage') && $subject->isTaughtBy($user);
    }

    public function update(User $user, Content $content): bool
    {
        return $user->hasPermission('contents.manage') && $content->subject->isTaughtBy($user);
    }

    public function delete(User $user, Content $content): bool
    {
        return $user->hasPermission('contents.manage') && $content->subject->isTaughtBy($user);
    }
}
