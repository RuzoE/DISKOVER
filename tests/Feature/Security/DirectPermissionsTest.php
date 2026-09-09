<?php

namespace Tests\Feature\Security;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_direct_permission_grants_access_without_a_role(): void
    {
        $user = User::factory()->withRole(RoleSlug::Coordinator->value)->create();

        $this->assertFalse($user->hasPermission('audit.view'));

        $user->syncDirectPermissions(['audit.view']);

        $this->assertTrue($user->fresh()->hasPermission('audit.view'));
    }

    public function test_admin_can_assign_direct_permissions_when_creating_a_user(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin->value)->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Analista',
                'email' => 'analista@diskover.test',
                'password' => 'secret-password-1',
                'password_confirmation' => 'secret-password-1',
                'status' => UserStatus::Active->value,
                'roles' => [RoleSlug::Coordinator->value],
                'direct_permissions' => ['audit.view'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'analista@diskover.test')->firstOrFail();

        $this->assertEqualsCanonicalizing(['audit.view'], $user->directPermissions->pluck('slug')->all());
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.permissions_changed']);
    }

    public function test_updating_direct_permissions_is_audited(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin->value)->create();
        $target = User::factory()->withRole(RoleSlug::Coordinator->value)->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'status' => $target->status->value,
                'roles' => [RoleSlug::Coordinator->value],
                'direct_permissions' => ['audit.view'],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($target->fresh()->hasPermission('audit.view'));
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.permissions_changed',
            'auditable_id' => $target->id,
        ]);
    }

    public function test_invalid_permission_slug_is_rejected(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin->value)->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'X',
                'email' => 'x@diskover.test',
                'password' => 'secret-password-1',
                'password_confirmation' => 'secret-password-1',
                'status' => UserStatus::Active->value,
                'direct_permissions' => ['does.not.exist'],
            ])
            ->assertSessionHasErrors('direct_permissions.0');
    }
}
