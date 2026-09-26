<?php

namespace App\Services\AI;

use App\DTOs\AI\ChatMessage;
use App\Enums\AiMessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiException;
use App\Services\AI\Exceptions\AiRateLimitedException;
use App\Services\Security\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Punto de entrada del módulo de IA. Los controladores hablan sólo con este
 * servicio; la elección de proveedor la resuelve el contenedor (AiServiceProvider).
 */
class AiService
{
    public const QUICK_CONTEXT = 'quick';

    public const RATE_LIMITED_REPLY = 'El asistente está atendiendo muchas consultas; inténtalo en un minuto.';

    public function __construct(
        private readonly AiProvider $provider,
        private readonly PromptManager $prompts,
        private readonly AcademicIntegrityGuard $integrity,
        private readonly AuditLogger $audit,
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
     * Revisa la integridad académica, añade el mensaje del usuario, pide la
     * respuesta al proveedor y persiste ambos. Nunca lanza: si el proveedor
     * falla, guarda un mensaje de error. Si el mensaje se bloquea, el
     * proveedor no lo recibe (ADR-0017).
     */
    public function sendMessage(AiConversation $conversation, User $user, string $content): AiMessage
    {
        $verdict = $this->integrity->inspect($user, $content);

        // El prompt se arma antes de guardar el mensaje nuevo para no duplicarlo en el historial.
        $payload = $verdict->isBlocked() ? [] : $this->prompts->build($user, $conversation, $content, $verdict->action);

        $conversation->messages()->create([
            'role' => AiMessageRole::User,
            'content' => $content,
            'integrity' => $verdict->isBlocked() || $verdict->isGuided() ? $verdict->action->value : null,
        ]);

        if ($verdict->isBlocked() || $verdict->isGuided()) {
            $this->audit->record(
                'ai.integrity.'.$verdict->action->value,
                $conversation,
                ['reason' => $verdict->reason],
                $verdict->isBlocked() ? 'Asistente IA: mensaje bloqueado por integridad académica' : 'Asistente IA: respuesta en modo guiado',
                $user,
            );
        }

        $assistant = $verdict->isBlocked()
            ? $conversation->messages()->create([
                'role' => AiMessageRole::Assistant,
                'content' => $verdict->reply,
                'integrity' => $verdict->action->value,
            ])
            : $this->reply($conversation, $payload);

        $conversation->forceFill(['last_message_at' => now()])->save();

        return $assistant;
    }

    /**
     * Conversación rápida del asistente flotante: reutiliza la activa del
     * usuario o crea una nueva titulada «Asistente rápido».
     */
    public function quickMessage(User $user, string $content): AiMessage
    {
        $conversation = $user->aiConversations()
            ->where('context_type', self::QUICK_CONTEXT)
            ->latest('id')
            ->first()
            ?? $user->aiConversations()->create([
                'title' => 'Asistente rápido',
                'context_type' => self::QUICK_CONTEXT,
                'provider' => $this->provider->name(),
                'model' => $this->provider->model(),
                'last_message_at' => now(),
            ]);

        return $this->sendMessage($conversation, $user, $content);
    }

    /**
     * @param  array<int, ChatMessage>  $payload
     */
    private function reply(AiConversation $conversation, array $payload): AiMessage
    {
        try {
            $response = $this->provider->chat($payload);

            return $conversation->messages()->create([
                'role' => AiMessageRole::Assistant,
                'content' => $response->content,
                'prompt_tokens' => $response->promptTokens,
                'completion_tokens' => $response->completionTokens,
            ]);
        } catch (AiException $e) {
            // Canal por defecto: en Laravel Cloud aparece en la pestaña Logs.
            Log::warning('ai.provider_failed', [
                'conversation_id' => $conversation->id,
                'provider' => $this->provider->name(),
                'error' => $e->getMessage(),
            ]);

            return $conversation->messages()->create([
                'role' => AiMessageRole::Assistant,
                'content' => $e instanceof AiRateLimitedException
                    ? self::RATE_LIMITED_REPLY
                    : 'No he podido generar una respuesta en este momento. Inténtalo de nuevo en unos minutos.',
                'failed' => true,
            ]);
        }
    }

    public function deleteConversation(AiConversation $conversation): void
    {
        DB::transaction(function () use ($conversation): void {
            $conversation->messages()->delete();
            $conversation->delete();
        });
    }
}
