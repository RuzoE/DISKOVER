<?php

namespace Tests\Fakes;

use App\DTOs\AI\ChatMessage;
use App\DTOs\AI\ChatResponse;
use App\Services\AI\Contracts\AiProvider;

/**
 * Proveedor de IA falso para pruebas: nunca llama a la red y guarda cada hilo
 * recibido para poder inspeccionar qué llegó (o no) al proveedor.
 */
class SpyAiProvider implements AiProvider
{
    /** @var array<int, array<int, ChatMessage>> */
    public array $calls = [];

    public function __construct(private readonly string $reply = 'Respuesta de prueba') {}

    public function chat(array $messages): ChatResponse
    {
        $this->calls[] = $messages;

        return new ChatResponse($this->reply, 'fake-model');
    }

    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        return 'fake-model';
    }

    public function called(): bool
    {
        return $this->calls !== [];
    }

    /**
     * Todo el texto enviado en la última llamada (sistema + historial + usuario).
     */
    public function lastPayloadText(): string
    {
        return collect(end($this->calls) ?: [])->map(fn (ChatMessage $m) => $m->content)->implode("\n");
    }

    public function lastSystemText(): string
    {
        return collect(end($this->calls) ?: [])
            ->filter(fn (ChatMessage $m) => $m->role->value === 'system')
            ->map(fn (ChatMessage $m) => $m->content)
            ->implode("\n");
    }
}
