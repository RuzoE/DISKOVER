<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_audited(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.login',
            'user_id' => $user->id,
        ]);
    }

    public function test_failed_login_is_audited_without_a_user(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'nope']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.failed',
            'user_id' => null,
        ]);
    }

    public function test_logout_is_audited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.logout',
            'user_id' => $user->id,
        ]);
    }
}
