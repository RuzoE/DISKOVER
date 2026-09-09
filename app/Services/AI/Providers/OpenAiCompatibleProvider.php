<?php

namespace App\Services\AI\Providers;

use App\DTOs\AI\ChatMessage;
use App\DTOs\AI\ChatResponse;
use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Proveedor para cualquier API compatible con el endpoint de chat de OpenAI
 * (`POST {base_url}/chat/completions`): OpenAI, Groq, OpenRouter, Ollama…
 * La URL base, el modelo y la clave se leen de config (que a su vez lee .env).
 */
class OpenAiCompatibleProvider implements AiProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        return (string) $this->config['model'];
    }

    public function chat(array $messages): ChatResponse
    {
        $payload = [
            'model' => $this->model(),
            'temperature' => (float) ($this->config['temperature'] ?? 0.4),
            'messages' => array_map(
                fn (ChatMessage $m) => $m->toArray(),
                $messages,
            ),
        ];

        try {
            $response = Http::baseUrl($this->config['base_url'])
                ->withToken((string) $this->config['api_key'])
                ->timeout((int) ($this->config['timeout'] ?? 30))
                ->acceptJson()
                ->post('/chat/completions', $payload);
        } catch (ConnectionException $e) {
            throw new AiException('No se pudo contactar con el proveedor de IA: '.$e->getMessage(), previous: $e);
        } catch (Throwable $e) {
            throw new AiException('Error inesperado al llamar al proveedor de IA.', previous: $e);
        }

        if ($response->failed()) {
            throw new AiException('El proveedor de IA respondió con un error ('.$response->status().').');
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new AiException('El proveedor de IA devolvió una respuesta vacía.');
        }

        return new ChatResponse(
            content: trim($content),
            model: (string) ($response->json('model') ?? $this->model()),
            promptTokens: $response->json('usage.prompt_tokens'),
            completionTokens: $response->json('usage.completion_tokens'),
        );
    }
}
