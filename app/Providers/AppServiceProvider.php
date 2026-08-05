<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The whole UI is Bootstrap 5, so pagination should match.
        Paginator::useBootstrapFive();

        // Behind Vercel's proxy Laravel would otherwise build http:// links,
        // which breaks the QR codes since they encode an absolute URL.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
