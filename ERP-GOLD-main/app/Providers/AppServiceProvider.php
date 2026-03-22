<?php

namespace App\Providers;

use App\Services\Branding\BrandLogoService;
use Illuminate\Support\Facades\View;
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
        View::composer('*', function ($view) {
            $view->with('brandLogoUrl', app(BrandLogoService::class)->logoUrl());
        });

        /*
        if (env('APP_ENV') === 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }
        */ 
    }
}
