<?php

namespace Tests\Concerns;

use App\Enums\RoleSlug;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Siembra el catálogo de roles y permisos del sistema antes de cada prueba y
 * ofrece atajos para crear usuarios ya vinculados a un rol.
 *
 * La clase de prueba debe usar también RefreshDatabase. El método
 * `setUpInteractsWithRoles` lo invoca Laravel automáticamente (mismo mecanismo
 * que RefreshDatabase), así que las pruebas no necesitan su propio `setUp()`.
 */
trait InteractsWithRoles
{
    protected function setUpInteractsWithRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function roleUser(RoleSlug|string $role, array $attributes = []): User
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;

        return User::factory()->withRole($slug)->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function adminUser(array $attributes = []): User
    {
        return $this->roleUser(RoleSlug::Admin, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function coordinatorUser(array $attributes = []): User
    {
        return $this->roleUser(RoleSlug::Coordinator, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function teacherUser(array $attributes = []): User
    {
        return $this->roleUser(RoleSlug::Teacher, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function studentUser(array $attributes = []): User
    {
        return $this->roleUser(RoleSlug::Student, $attributes);
    }

    /**
     * Crea un usuario con el rol indicado y lo deja autenticado.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function actingAsRole(RoleSlug|string $role, array $attributes = []): User
    {
        $user = $this->roleUser($role, $attributes);
        $this->actingAs($user);

        return $user;
    }
}
