<?php

namespace App\Services\Security;

use App\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Punto de entrada del módulo de seguridad para la gestión de roles y su
 * relación con los permisos.
 */
class RoleService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data  Datos ya validados por el Form Request.
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => Arr::get($data, 'description'),
                'is_system' => false,
            ]);

            $permissions = Arr::get($data, 'permissions', []);
            $role->syncPermissionsBySlug($permissions);
            $this->audit->record('role.permissions_set', $role, ['permissions' => $permissions]);

            return $role;
        });
    }

    /**
     * @param  array<string, mixed>  $data  Datos ya validados por el Form Request.
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->update([
                'name' => $data['name'],
                'slug' => $role->is_system ? $role->slug : $data['slug'],
                'description' => Arr::get($data, 'description'),
            ]);

            $old = $role->permissions->pluck('slug')->sort()->values()->all();
            $new = collect(Arr::get($data, 'permissions', []))->map('strval')->sort()->values()->all();
            $role->syncPermissionsBySlug($new);

            if ($old !== $new) {
                $this->audit->record('role.permissions_changed', $role, ['old' => $old, 'new' => $new]);
            }

            return $role;
        });
    }

    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => 'Los roles del sistema no se pueden eliminar.',
            ]);
        }

        DB::transaction(function () use ($role): void {
            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();
        });
    }
}
