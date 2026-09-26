<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            // Force these here rather than relying on env vars being set correctly —
            // the session cookie should never go out over plain HTTP in production.
            config(['session.secure' => true]);
            config(['session.same_site' => 'lax']);
        }
    }
}
