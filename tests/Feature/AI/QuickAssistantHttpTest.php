<?php

namespace Tests\Feature\AI;

use App\Enums\RoleSlug;
use App\Services\AI\Contracts\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\Fakes\SpyAiProvider;
use Tests\TestCase;

class QuickAssistantHttpTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(AiProvider::class, new SpyAiProvider('**Hola** desde el tutor'));
    }

    public function test_guests_are_rejected(): void
    {
        $this->post(route('assistant.quick'), ['message' => 'hola'])->assertRedirect(route('login'));
        $this->postJson(route('assistant.quick'), ['message' => 'hola'])->assertUnauthorized();
    }

    public function test_student_gets_a_json_reply_in_a_reused_quick_conversation(): void
    {
        $student = $this->roleUser(RoleSlug::Student);

        $this->actingAs($student)->postJson(route('assistant.quick'), ['message' => '¿Qué trabajos me faltan?'])
            ->assertOk()
            ->assertJson(['reply' => '**Hola** desde el tutor', 'failed' => false])
            ->assertJsonPath('html', fn (string $html) => str_contains($html, '<strong>Hola</strong>'));

        $this->actingAs($student)->postJson(route('assistant.quick'), ['message' => '¿Cómo voy?'])->assertOk();

        $this->assertSame(1, $student->aiConversations()->count());
        $conversation = $student->aiConversations()->firstOrFail();
        $this->assertSame('Asistente rápido', $conversation->title);
        $this->assertSame('quick', $conversation->context_type);
        $this->assertSame(4, $conversation->messages()->count());
    }

    public function test_message_is_validated(): void
    {
        $this->actingAs($this->roleUser(RoleSlug::Student))
            ->postJson(route('assistant.quick'), ['message' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }

    public function test_only_students_and_admins_can_use_it(): void
    {
        $this->actingAs($this->roleUser(RoleSlug::Teacher))
            ->postJson(route('assistant.quick'), ['message' => 'hola'])
            ->assertForbidden();

        $this->actingAs($this->adminUser())
            ->postJson(route('assistant.quick'), ['message' => 'hola'])
            ->assertOk();
    }

    public function test_rate_limit_applies(): void
    {
        config(['dsle.ai.rate_limit_per_minute' => 2]);
        $student = $this->roleUser(RoleSlug::Student);

        $this->actingAs($student)->postJson(route('assistant.quick'), ['message' => 'uno'])->assertOk();
        $this->actingAs($student)->postJson(route('assistant.quick'), ['message' => 'dos'])->assertOk();
        $this->actingAs($student)->postJson(route('assistant.quick'), ['message' => 'tres'])->assertStatus(429);
    }

    public function test_floating_button_is_rendered_only_for_students_and_admins(): void
    {
        $this->actingAs($this->roleUser(RoleSlug::Student))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-quick-assistant', false)
            ->assertSee('¿Qué trabajos me faltan?');

        $this->actingAs($this->roleUser(RoleSlug::Teacher))->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-quick-assistant', false);
    }
}
