<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

/**
 * Cobertura de los middleware de acceso `active` y `role`, que no se ejercen
 * de forma aislada en las pruebas por módulo.
 */
class AccessControlMiddlewareTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_active_middleware_locks_out_a_suspended_account(): void
    {
        $user = User::factory()->suspended()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_active_middleware_locks_out_an_inactive_account(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_active_middleware_lets_an_active_account_through(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_role_middleware_blocks_a_user_without_the_required_role(): void
    {
        $student = $this->studentUser();

        $this->actingAs($student)
            ->get(route('coordinator.courses.index'))
            ->assertForbidden();
    }

    public function test_role_middleware_allows_a_user_with_the_required_role(): void
    {
        $coordinator = $this->coordinatorUser();

        $this->actingAs($coordinator)
            ->get(route('coordinator.courses.index'))
            ->assertOk();
    }

    public function test_role_middleware_always_allows_the_admin(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get(route('coordinator.courses.index'))
            ->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
