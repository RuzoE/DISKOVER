<?php

namespace App\Rules;

use App\Enums\RoleSlug;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Comprueba que el valor sea el id de un usuario con rol de docente.
 * Acepta null (asignatura sin docente asignado).
 */
class IsTeacher implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $isTeacher = User::whereKey($value)
            ->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Teacher->value))
            ->exists();

        if (! $isTeacher) {
            $fail('El docente seleccionado no es válido.');
        }
    }
}
