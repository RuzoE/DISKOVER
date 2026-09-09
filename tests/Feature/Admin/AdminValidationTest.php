<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Reglas de los Form Requests de administración: los casos de rechazo que las
 * pruebas de "camino feliz" no cubren.
 */
class AdminValidationTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private function base(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Persona Nueva',
            'email' => 'persona.nueva@diskover.test',
            'password' => 'secret-password-1',
            'password_confirmation' => 'secret-password-1',
            'status' => UserStatus::Active->value,
        ], $overrides);
    }

    public function test_user_email_must_be_unique(): void
    {
        $existing = User::factory()->create(['email' => 'taken@diskover.test']);

        $this->actingAs($this->adminUser())
            ->post(route('admin.users.store'), $this->base(['email' => $existing->email]))
            ->assertSessionHasErrors('email');
    }

    public function test_user_password_must_be_confirmed(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.users.store'), $this->base(['password_confirmation' => 'mismatch']))
            ->assertSessionHasErrors('password');
    }

    public function test_user_role_slug_must_exist(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.users.store'), $this->base(['roles' => ['ghost-role']]))
            ->assertSessionHasErrors('roles.0');
    }

    public function test_user_can_be_updated_keeping_its_own_email(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create(['email' => 'keep@diskover.test']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'Nombre Cambiado',
                'email' => 'keep@diskover.test',
                'status' => UserStatus::Active->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('Nombre Cambiado', $target->fresh()->name);
    }

    public function test_role_slug_must_be_unique(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.roles.store'), [
                'name' => 'Duplicado',
                'slug' => RoleSlug::Teacher->value,
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_system_role_slug_cannot_be_changed(): void
    {
        $admin = $this->adminUser();
        $teacherRole = Role::where('slug', RoleSlug::Teacher->value)->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $teacherRole), [
                'name' => 'Docente renombrado',
                'slug' => 'docente-x',
                'permissions' => [],
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame(RoleSlug::Teacher->value, $teacherRole->fresh()->slug);
    }

    public function test_role_permissions_must_exist(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.roles.store'), [
                'name' => 'Rol raro',
                'slug' => 'rol-raro',
                'permissions' => ['not.a.real.permission'],
            ])
            ->assertSessionHasErrors('permissions.0');
    }
}
