<?php

namespace Tests\Feature\Security;

use App\Enums\RoleSlug;
use App\Models\User;
use App\Services\Security\AuditLogger;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_open_the_audit_viewer(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin->value)->create();
        app(AuditLogger::class)->record('demo.event', null, [], 'Evento de prueba');

        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('demo.event');
    }

    public function test_user_with_direct_permission_can_open_the_audit_viewer(): void
    {
        $user = User::factory()->withRole(RoleSlug::Coordinator->value)->create();
        $user->syncDirectPermissions(['audit.view']);

        $this->actingAs($user->fresh())
            ->get(route('admin.audit.index'))
            ->assertOk();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $this->actingAs($user)
            ->get(route('admin.audit.index'))
            ->assertForbidden();
    }

    public function test_audit_log_can_be_exported_as_csv(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin->value)->create();
        app(AuditLogger::class)->record('demo.event', null, [], 'Evento de prueba');

        $response = $this->actingAs($admin)->get(route('admin.audit.index', ['export' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_event_filter_narrows_the_result(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin->value)->create();
        $logger = app(AuditLogger::class);
        $logger->record('alpha.event', null, [], 'Alpha description here');
        $logger->record('beta.event', null, [], 'Beta description here');

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['event' => 'alpha.event']))
            ->assertOk()
            ->assertSee('Alpha description here')
            ->assertDontSee('Beta description here');
    }
}
