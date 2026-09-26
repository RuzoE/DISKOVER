<?php

namespace Tests\Feature\AI;

use App\Services\AI\Contracts\AiProvider;
use Tests\Fakes\SpyAiProvider;
use Tests\TestCase;

class AiCheckCommandTest extends TestCase
{
    public function test_it_fails_when_running_in_offline_mode(): void
    {
        $this->artisan('dsle:ai-check')
            ->expectsOutputToContain('modo sin conexión')
            ->assertFailed();
    }

    public function test_it_calls_the_provider_without_printing_the_key(): void
    {
        config(['dsle.ai.api_key' => 'gsk_super_secret_value']);
        $this->app->instance(AiProvider::class, new SpyAiProvider('ok'));

        $this->artisan('dsle:ai-check')
            ->expectsOutputToContain('Conexión correcta')
            ->doesntExpectOutputToContain('gsk_super_secret_value')
            ->assertSuccessful();
    }
}
