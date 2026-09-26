<?php

namespace Tests\Feature\AI;

use App\DTOs\AI\ChatMessage;
use App\Services\AI\Exceptions\AiException;
use App\Services\AI\Exceptions\AiRateLimitedException;
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
            'max_tokens' => 321,
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
                && $request['max_tokens'] === 321
                && ! isset($request['reasoning_effort'])
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

    public function test_429_becomes_a_rate_limited_exception(): void
    {
        Http::fake([
            'api.example.test/*' => Http::response(['error' => 'rate limit'], 429),
        ]);

        $this->expectException(AiRateLimitedException::class);
        $this->provider()->chat([ChatMessage::user('hola')]);
    }

    public function test_error_message_from_the_provider_is_kept_for_the_logs(): void
    {
        Http::fake([
            'api.example.test/*' => Http::response(['error' => ['message' => 'Invalid API Key']], 401),
        ]);

        $this->expectException(AiException::class);
        $this->expectExceptionMessage('(401): Invalid API Key');
        $this->provider()->chat([ChatMessage::user('hola')]);
    }

    public function test_reasoning_effort_is_sent_only_when_configured(): void
    {
        Http::fake([
            'api.example.test/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]]),
        ]);

        (new OpenAiCompatibleProvider([
            'base_url' => 'https://api.example.test/v1',
            'api_key' => 'k',
            'model' => 'openai/gpt-oss-120b',
            'reasoning_effort' => 'low',
        ]))->chat([ChatMessage::user('hola')]);

        Http::assertSent(fn ($request) => $request['reasoning_effort'] === 'low');
    }
}
