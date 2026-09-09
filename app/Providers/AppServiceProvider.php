<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use App\Listeners\LearningEventSubscriber;
use App\Models\AiConversation;
use App\Models\ImmersiveExperience;
use App\Models\ImmersiveSession;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(AuthEventSubscriber::class);
        Event::subscribe(LearningEventSubscriber::class);

        Paginator::defaultView('vendor.pagination.dsle');
        Paginator::defaultSimpleView('vendor.pagination.dsle');

        Route::model('conversation', AiConversation::class);
        Route::model('experience', ImmersiveExperience::class);
        Route::model('session', ImmersiveSession::class);

        RateLimiter::for('immersive', fn (Request $request) => Limit::perMinute(
            (int) config('dsle.immersive.api_rate_limit_per_minute', 60)
        )->by('immersive:'.($request->route('token') ?: $request->ip())));
    }
}
