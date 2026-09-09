<?php

namespace App\Services\AI;

use App\Enums\AiMessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Punto de entrada del módulo de IA. Los controladores hablan sólo con este
 * servicio; la elección de proveedor la resuelve el contenedor (AiServiceProvider).
 */
class AiService
{
    public function __construct(
        private readonly AiProvider $provider,
        private readonly PromptManager $prompts,
    ) {}

    public function isRealProvider(): bool
    {
        return $this->provider->name() !== 'stub';
    }

    public function providerName(): string
    {
        return $this->provider->name();
    }

    public function startConversation(User $user, string $firstMessage, string $contextType = 'general', ?int $contextId = null): AiConversation
    {
        $conversation = $user->aiConversations()->create([
            'title' => Str::limit(trim($firstMessage), 60, '…') ?: 'Nueva conversación',
            'context_type' => $contextType,
            'context_id' => $contextId,
            'provider' => $this->provider->name(),
            'model' => $this->provider->model(),
            'last_message_at' => now(),
        ]);

        $this->sendMessage($conversation, $user, $firstMessage);

        return $conversation;
    }

    /**
     * Añade el mensaje del usuario, pide la respuesta al proveedor y persiste
     * ambos. Nunca lanza: si el proveedor falla, guarda un mensaje de error.
     */
    public function sendMessage(AiConversation $conversation, User $user, string $content): AiMessage
    {
        $userMessage = $conversation->messages()->create([
            'role' => AiMessageRole::User,
            'content' => $content,
        ]);

        $payload = $this->prompts->build($user, $conversation, $content);

        try {
            $response = $this->provider->chat($payload);

            $assistant = $conversation->messages()->create([
                'role' => AiMessageRole::Assistant,
                'content' => $response->content,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
            ]);
        } catch (AiException $e) {
            Log::channel('stack')->warning('ai.provider_failed', [
                'conversation_id' => $conversation->id,
                'provider' => $this->provider->name(),
                'error' => $e->getMessage(),
            ]);

            $assistant = $conversation->messages()->create([
                'role' => AiMessageRole::Assistant,
                'content' => 'No he podido generar una respuesta en este momento. Inténtalo de nuevo en unos minutos.',
                'failed' => true,
            ]);
        }

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $assistant;
    }

    public function deleteConversation(AiConversation $conversation): void
    {
        DB::transaction(function () use ($conversation): void {
            $conversation->messages()->delete();
            $conversation->delete();
        });
    }
}
