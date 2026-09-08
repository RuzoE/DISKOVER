<?php

namespace Tests\Unit;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_reports_assigned_role(): void
    {
        $role = Role::factory()->create(['slug' => 'tutor']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->fresh()->hasRole('tutor'));
        $this->assertFalse($user->fresh()->hasRole('other'));
    }

    public function test_permissions_are_resolved_through_roles(): void
    {
        $permission = Permission::factory()->create(['slug' => 'reports.view']);
        $role = Role::factory()->create();
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->fresh()->hasPermission('reports.view'));
        $this->assertFalse($user->fresh()->hasPermission('reports.export'));
    }

    public function test_admin_role_short_circuits_permission_checks(): void
    {
        $adminRole = Role::factory()->create(['slug' => RoleSlug::Admin->value, 'is_system' => true]);
        $user = User::factory()->create();
        $user->roles()->attach($adminRole);

        $this->assertTrue($user->fresh()->hasPermission('anything.at.all'));
        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_sync_roles_replaces_existing_assignments(): void
    {
        Role::factory()->create(['slug' => 'a']);
        Role::factory()->create(['slug' => 'b']);
        $user = User::factory()->create();

        $user->syncRoles(['a']);
        $this->assertEqualsCanonicalizing(['a'], $user->fresh()->roles->pluck('slug')->all());

        $user->syncRoles(['b']);
        $this->assertEqualsCanonicalizing(['b'], $user->fresh()->roles->pluck('slug')->all());
    }
}
