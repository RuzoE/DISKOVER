<?php

namespace Tests\Feature\AI;

use App\DTOs\AI\ChatMessage;
use App\Services\AI\Exceptions\AiException;
use App\Services\AI\Providers\OpenAiCompatibleProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiCompatibleProviderTest extends TestCase
{
    private function provider(): OpenAiCompatibleProvider
    {
        return new OpenAiCompatibleProvider([
            'base_url' => 'https://api.example.test/v1',
            'api_key' => 'secret-key',
            'model' => 'demo-model',
            'timeout' => 5,
            'temperature' => 0.3,
        ]);
    }

    public function test_it_calls_the_chat_completions_endpoint_and_parses_the_reply(): void
    {
        Http::fake([
            'api.example.test/v1/chat/completions' => Http::response([
                'model' => 'demo-model',
                'choices' => [['message' => ['content' => 'Hola, soy la IA.']]],
                'usage' => ['prompt_tokens' => 20, 'completion_tokens' => 5],
            ]),
        ]);

        $result = $this->provider()->chat([
            ChatMessage::system('sistema'),
            ChatMessage::user('hola'),
        ]);

        $this->assertSame('Hola, soy la IA.', $result->content);
        $this->assertSame(20, $result->promptTokens);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer secret-key')
                && $request->url() === 'https://api.example.test/v1/chat/completions'
                && $request['model'] === 'demo-model'
                && $request['messages'][1] === ['role' => 'user', 'content' => 'hola'];
        });
    }

    public function test_http_error_becomes_an_ai_exception(): void
    {
        Http::fake([
            'api.example.test/*' => Http::response(['error' => 'nope'], 500),
        ]);

        $this->expectException(AiException::class);
        $this->provider()->chat([ChatMessage::user('hola')]);
    }

    public function test_empty_content_becomes_an_ai_exception(): void
    {
        Http::fake([
            'api.example.test/*' => Http::response(['choices' => [['message' => ['content' => '   ']]]]),
        ]);

        $this->expectException(AiException::class);
        $this->provider()->chat([ChatMessage::user('hola')]);
    }
}
