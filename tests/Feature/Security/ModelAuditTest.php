<?php

namespace Tests\Feature\Security;

use App\Enums\RoleSlug;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_creating_an_auditable_model_writes_a_created_event(): void
    {
        $actor = User::factory()->withRole(RoleSlug::Admin->value)->create();
        $this->actingAs($actor);

        $role = Role::create(['name' => 'Tutor', 'slug' => 'tutor', 'is_system' => false]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'role.created',
            'auditable_type' => $role->getMorphClass(),
            'auditable_id' => $role->id,
            'user_id' => $actor->id,
        ]);
    }

    public function test_updating_an_auditable_model_records_the_diff(): void
    {
        $actor = User::factory()->withRole(RoleSlug::Admin->value)->create();
        $this->actingAs($actor);

        $role = Role::create(['name' => 'Tutor', 'slug' => 'tutor', 'is_system' => false]);
        $role->update(['name' => 'Tutor senior']);

        $log = AuditLog::where('event', 'role.updated')
            ->where('auditable_id', $role->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Tutor', $log->properties['old']['name']);
        $this->assertSame('Tutor senior', $log->properties['new']['name']);
    }

    public function test_deleting_an_auditable_model_writes_a_deleted_event(): void
    {
        $actor = User::factory()->withRole(RoleSlug::Admin->value)->create();
        $this->actingAs($actor);

        $role = Role::create(['name' => 'Tutor', 'slug' => 'tutor', 'is_system' => false]);
        $id = $role->id;
        $role->delete();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'role.deleted',
            'auditable_id' => $id,
        ]);
    }
}
