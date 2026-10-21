<?php

namespace App\Providers;

use App\Utils\DateTimeFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DateTimeFactory::class, function ($app) {
            return new DateTimeFactory((string) $app['request']->input('dateFormat', 'default'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
