<?php

namespace App\Providers;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Providers\OpenAiCompatibleProvider;
use App\Services\AI\Providers\StubAiProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiProvider::class, function () {
            $config = config('dsle.ai');

            if (($config['provider'] ?? null) === 'openai' && filled($config['api_key'] ?? null)) {
                return new OpenAiCompatibleProvider($config);
            }

            return new StubAiProvider;
        });
    }

    public function boot(): void
    {
        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(
            (int) config('dsle.ai.rate_limit_per_minute', 12)
        )->by('ai:'.($request->user()?->id ?: $request->ip())));
    }
}
