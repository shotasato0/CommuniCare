<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
    public function boot()
    {
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $tenant = Auth::user()->tenant;
                Log::info('Tenant in AppServiceProvider:', ['tenant' => $tenant]);
                $view->with('tenant', $tenant);
            } else {
                Log::info('No Tenant associated');
                $view->with('tenant', null);
            }
        });
    }
}
