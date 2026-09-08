<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

/**
 * Gestión de cursos: reservada a coordinación/administración
 * (permiso `courses.manage`). El acceso de los estudiantes a "sus" cursos
 * se resuelve por inscripción en los controladores del área de estudiante.
 */
class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('courses.manage');
    }

    public function view(User $user, Course $course): bool
    {
        return $user->hasPermission('courses.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('courses.manage');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasPermission('courses.manage');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->hasPermission('courses.manage');
    }
}
