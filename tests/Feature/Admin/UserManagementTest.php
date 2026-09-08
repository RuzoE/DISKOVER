<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_list_users(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_non_privileged_user_cannot_access_user_admin(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->actingAs($student)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_user_with_roles(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Nuevo Docente',
                'email' => 'nuevo.docente@diskover.test',
                'password' => 'secret-password-1',
                'password_confirmation' => 'secret-password-1',
                'status' => UserStatus::Active->value,
                'roles' => [RoleSlug::Teacher->value],
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'nuevo.docente@diskover.test')->firstOrFail();

        $this->assertTrue($user->hasRole(RoleSlug::Teacher));
        $this->assertTrue(auth()->attempt(['email' => $user->email, 'password' => 'secret-password-1']));
    }

    public function test_admin_can_update_a_user_without_changing_password(): void
    {
        $target = User::factory()->create(['name' => 'Antes']);

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $target), [
                'name' => 'Después',
                'email' => $target->email,
                'status' => UserStatus::Suspended->value,
                'roles' => [],
            ])
            ->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertSame('Después', $target->name);
        $this->assertSame(UserStatus::Suspended, $target->status);
        $this->assertTrue(auth()->attempt(['email' => $target->email, 'password' => 'password']));
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
