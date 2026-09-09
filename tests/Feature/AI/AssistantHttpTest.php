<?php

namespace Tests\Feature\AI;

use App\Enums\RoleSlug;
use App\Models\AiConversation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_starts_a_conversation_and_gets_a_reply(): void
    {
        $user = User::factory()->withRole(RoleSlug::Student->value)->create();

        $response = $this->actingAs($user)->post(route('assistant.store'), [
            'message' => '¿Cómo organizo mi semana de estudio?',
        ]);

        $conversation = $user->aiConversations()->firstOrFail();
        $response->assertRedirect(route('assistant.show', $conversation));

        $this->assertSame(2, $conversation->messages()->count()); // user + assistant
        $this->assertSame('assistant', $conversation->messages()->get()->last()->role->value);

        $this->actingAs($user)->get(route('assistant.show', $conversation))
            ->assertOk()
            ->assertSee('¿Cómo organizo mi semana de estudio?')
            ->assertSee('Asistente educativo de DSLE');
    }

    public function test_message_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('assistant.store'), ['message' => ''])
            ->assertSessionHasErrors('message');
    }

    public function test_user_cannot_view_or_delete_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $conversation = AiConversation::factory()->for($owner)->create();

        $this->actingAs($other)->get(route('assistant.show', $conversation))->assertForbidden();
        $this->actingAs($other)->delete(route('assistant.destroy', $conversation))->assertForbidden();
        $this->actingAs($other)->post(route('assistant.message', $conversation), ['message' => 'hola'])->assertForbidden();
    }

    public function test_owner_can_send_follow_up_and_delete(): void
    {
        $user = User::factory()->create();
        $conversation = AiConversation::factory()->for($user)->create();

        $this->actingAs($user)->post(route('assistant.message', $conversation), ['message' => 'Otra duda'])
            ->assertRedirect(route('assistant.show', $conversation));
        $this->assertSame(2, $conversation->messages()->count());

        $this->actingAs($user)->delete(route('assistant.destroy', $conversation))
            ->assertRedirect(route('assistant.index'));
        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
    }

    public function test_rate_limiting_blocks_excessive_requests(): void
    {
        config(['dsle.ai.rate_limit_per_minute' => 2]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('assistant.store'), ['message' => 'uno'])->assertRedirect();
        $this->actingAs($user)->post(route('assistant.store'), ['message' => 'dos'])->assertRedirect();
        $this->actingAs($user)->post(route('assistant.store'), ['message' => 'tres'])->assertStatus(429);
    }
}
