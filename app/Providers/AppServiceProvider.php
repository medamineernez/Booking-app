<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Event;
use App\Observers\EventObserver;

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
        // Register observers
        Event::observe(EventObserver::class);

        // Register push notification channel
        \Illuminate\Support\Facades\Notification::extend('push', function ($app) {
            return new \App\Notifications\Channels\PushChannel();
        });
    }
}
