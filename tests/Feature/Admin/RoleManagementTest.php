<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->withRole(RoleSlug::Admin->value)->create();
    }

    public function test_admin_can_create_a_custom_role(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.roles.store'), [
                'name' => 'Tutor',
                'slug' => 'tutor',
                'description' => 'Acompañamiento a estudiantes',
                'permissions' => ['users.view'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::where('slug', 'tutor')->firstOrFail();
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->hasPermission('users.view'));
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $adminRole = Role::where('slug', RoleSlug::Admin->value)->firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('admin.roles.destroy', $adminRole))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
    }

    public function test_custom_role_can_be_deleted(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_coordinator_can_view_but_not_create_roles(): void
    {
        $coordinator = User::factory()->withRole(RoleSlug::Coordinator->value)->create();

        $this->actingAs($coordinator)->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($coordinator)->get(route('admin.roles.create'))->assertForbidden();
    }
}
