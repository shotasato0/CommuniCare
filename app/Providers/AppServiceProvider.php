<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot()
    {
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $tenant = Auth::user()->tenant;
                $view->with('tenant', $tenant);
            } else {
                $view->with('tenant', null);
            }
        });
    }
}
