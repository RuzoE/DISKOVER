<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Crea el catálogo de permisos y los roles base del sistema.
 * Es idempotente: puede ejecutarse varias veces sin duplicar datos.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Catálogo de permisos: slug => [nombre, grupo, descripción].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private array $permissions = [
        'users.view' => ['Ver usuarios', 'users', 'Listar y consultar cuentas de usuario.'],
        'users.create' => ['Crear usuarios', 'users', 'Registrar nuevas cuentas de usuario.'],
        'users.update' => ['Editar usuarios', 'users', 'Modificar datos y roles de un usuario.'],
        'users.delete' => ['Eliminar usuarios', 'users', 'Dar de baja cuentas de usuario.'],
        'roles.view' => ['Ver roles', 'roles', 'Listar y consultar roles y permisos.'],
        'roles.create' => ['Crear roles', 'roles', 'Definir nuevos roles personalizados.'],
        'roles.update' => ['Editar roles', 'roles', 'Modificar un rol y sus permisos.'],
        'roles.delete' => ['Eliminar roles', 'roles', 'Eliminar roles personalizados.'],

        // Gestión académica (Fase 2)
        'courses.manage' => ['Gestionar cursos', 'academic', 'Crear, editar y eliminar cursos.'],
        'subjects.manage' => ['Gestionar asignaturas', 'academic', 'Crear, editar y eliminar asignaturas y asignar docentes.'],
        'enrollments.manage' => ['Gestionar inscripciones', 'academic', 'Inscribir y dar de baja a estudiantes en cursos.'],
        'contents.manage' => ['Gestionar contenidos', 'academic', 'Crear, editar y eliminar contenidos de las asignaturas que imparte.'],
    ];

    /**
     * Roles base: slug => [nombre, descripción, permisos].
     *
     * @var array<string, array{name: string, description: string, permissions: array<int, string>|string}>
     */
    private function roleDefinitions(): array
    {
        return [
            RoleSlug::Admin->value => [
                'name' => RoleSlug::Admin->label(),
                'description' => 'Acceso total a la plataforma.',
                'permissions' => '*',
            ],
            RoleSlug::Coordinator->value => [
                'name' => RoleSlug::Coordinator->label(),
                'description' => 'Supervisión académica: cursos, asignaturas e inscripciones.',
                'permissions' => [
                    'users.view', 'roles.view',
                    'courses.manage', 'subjects.manage', 'enrollments.manage',
                ],
            ],
            RoleSlug::Teacher->value => [
                'name' => RoleSlug::Teacher->label(),
                'description' => 'Imparte asignaturas y gestiona sus contenidos.',
                'permissions' => ['contents.manage'],
            ],
            RoleSlug::Student->value => [
                'name' => RoleSlug::Student->label(),
                'description' => 'Acceso a su experiencia de aprendizaje.',
                'permissions' => [],
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->permissions as $slug => [$name, $group, $description]) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'group' => $group, 'description' => $description],
            );
        }

        $allSlugs = array_keys($this->permissions);

        foreach ($this->roleDefinitions() as $slug => $definition) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => true,
                ],
            );

            $permissionSlugs = $definition['permissions'] === '*'
                ? $allSlugs
                : $definition['permissions'];

            $role->syncPermissionsBySlug($permissionSlugs);
        }

        Cache::forget('dsle.permission-slugs');
    }
}
