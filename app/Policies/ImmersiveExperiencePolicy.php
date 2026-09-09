<?php

namespace App\Policies;

use App\Models\ImmersiveExperience;
use App\Models\User;

/**
 * Gestión del catálogo de experiencias inmersivas: coordinación/administración
 * (permiso `immersive.manage`). El consumo por el estudiante se resuelve por
 * inscripción en los controladores del área de aprendizaje.
 */
class ImmersiveExperiencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('immersive.manage');
    }

    public function view(User $user, ImmersiveExperience $experience): bool
    {
        return $user->hasPermission('immersive.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('immersive.manage');
    }

    public function update(User $user, ImmersiveExperience $experience): bool
    {
        return $user->hasPermission('immersive.manage');
    }

    public function delete(User $user, ImmersiveExperience $experience): bool
    {
        return $user->hasPermission('immersive.manage');
    }
}
