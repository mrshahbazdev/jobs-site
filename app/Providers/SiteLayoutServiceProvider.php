<?php

namespace App\Providers;

use App\View\Composers\GlobalSiteComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SiteLayoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Attach the GlobalSiteComposer to the main layout and components
        View::composer(['components.layout', 'components.header', 'components.footer', 'home'], GlobalSiteComposer::class);
    }
}
