<?php

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
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

        Paginator::defaultView('vendor.pagination.dsle');
        Paginator::defaultSimpleView('vendor.pagination.dsle');
    }
}
