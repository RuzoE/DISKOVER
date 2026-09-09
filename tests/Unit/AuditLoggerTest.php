<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Security\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_persists_an_append_only_row(): void
    {
        $actor = User::factory()->create();

        $log = app(AuditLogger::class)->record('demo.event', $actor, ['foo' => 'bar'], 'Prueba', $actor);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event' => 'demo.event',
            'user_id' => $actor->id,
            'auditable_type' => $actor->getMorphClass(),
            'auditable_id' => $actor->id,
            'description' => 'Prueba',
        ]);
        $this->assertNull(AuditLog::UPDATED_AT);
        $this->assertSame(['foo' => 'bar'], $log->fresh()->properties);
    }

    public function test_sensitive_keys_are_redacted(): void
    {
        $redacted = app(AuditLogger::class)->redact([
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'api_token' => 'abc123',
            'launch_token' => 'xyz',
            'nested' => ['current_password' => 'p', 'name' => 'Ada'],
            'name' => 'Ada Lovelace',
        ]);

        $this->assertSame('••••', $redacted['password']);
        $this->assertSame('••••', $redacted['password_confirmation']);
        $this->assertSame('••••', $redacted['api_token']);
        $this->assertSame('••••', $redacted['launch_token']);
        $this->assertSame('••••', $redacted['nested']['current_password']);
        $this->assertSame('Ada', $redacted['nested']['name']);
        $this->assertSame('Ada Lovelace', $redacted['name']);
    }

    public function test_empty_properties_are_stored_as_null(): void
    {
        $log = app(AuditLogger::class)->record('demo.empty');

        $this->assertNull($log->fresh()->properties);
        $this->assertNull($log->user_id);
    }
}
