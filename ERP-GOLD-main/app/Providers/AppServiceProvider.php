<?php

namespace App\Providers;

use App\Services\Branding\BrandLogoService;
use App\Services\Navigation\AdminSidebarBuilder;
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
            $adminUser = auth('admin-web')->user();
            $sidebarBuilder = app(AdminSidebarBuilder::class);

            $view->with('brandLogoUrl', app(BrandLogoService::class)->logoUrl());
            $view->with('adminSidebarMode', $sidebarBuilder->modeFor($adminUser));
            $view->with('ownerSidebarSections', $sidebarBuilder->ownerSections($adminUser));
            $view->with('operationalAdminSections', $sidebarBuilder->operationalAdminSections($adminUser));
        });

        /*
        if (env('APP_ENV') === 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }
        */ 
    }
}
