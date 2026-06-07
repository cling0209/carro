<?php

namespace App\Providers;

use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\URL;
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
        \Illuminate\Pagination\Paginator::useBootstrapFive();
        Markdown::theme('romulo');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
