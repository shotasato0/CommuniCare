<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Unit;
use App\Models\User;

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
        // Inertia.jsのRequestマクロを登録
        // Inertia.jsのミドルウェアで使用される可能性があるため、確実に登録する
        Request::macro('inertia', function () {
            return \Inertia\Inertia::getShared();
        });
    }
}
