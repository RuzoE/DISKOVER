<?php

namespace App\Models\Concerns;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Da a un modelo (User) la capacidad de tener roles y permisos. Los permisos
 * llegan por sus roles y, además, de forma directa (pivote `permission_user`,
 * Fase 11). La autorización real se resuelve vía Gate/Policies consultando estos
 * métodos.
 */
trait HasRoles
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Permisos concedidos directamente al usuario, además de los de sus roles.
     *
     * @return BelongsToMany<Permission, $this>
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function hasRole(RoleSlug|string $role): bool
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;

        return $this->roles->contains('slug', $slug);
    }

    /**
     * @param  array<int, RoleSlug|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleSlug::Admin);
    }

    /**
     * Todos los slugs de permiso del usuario: los de sus roles más los directos.
     *
     * @return Collection<int, string>
     */
    public function permissionSlugs(): Collection
    {
        $fromRoles = $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'));

        return $fromRoles
            ->merge($this->directPermissions->pluck('slug'))
            ->unique()
            ->values();
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permissionSlugs()->contains($permissionSlug);
    }

    /**
     * @param  array<int, RoleSlug|string>  $roles
     */
    public function syncRoles(array $roles): void
    {
        $slugs = array_map(
            fn (RoleSlug|string $role) => $role instanceof RoleSlug ? $role->value : $role,
            $roles,
        );

        $ids = Role::whereIn('slug', $slugs)->pluck('id')->all();

        $this->roles()->sync($ids);
        $this->unsetRelation('roles');
    }

    public function assignRole(RoleSlug|string $role): void
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;
        $roleId = Role::where('slug', $slug)->value('id');

        if ($roleId !== null) {
            $this->roles()->syncWithoutDetaching([$roleId]);
            $this->unsetRelation('roles');
        }
    }

    /**
     * Reemplaza los permisos directos del usuario a partir de sus slugs.
     *
     * @param  array<int, string>  $slugs
     */
    public function syncDirectPermissions(array $slugs): void
    {
        $ids = Permission::whereIn('slug', $slugs)->pluck('id')->all();

        $this->directPermissions()->sync($ids);
        $this->unsetRelation('directPermissions');
    }
}
