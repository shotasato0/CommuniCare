<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Unit;
use App\Models\User;
use App\Services\ContextualLogService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // コンテキスト対応ログサービスをシングルトンとして登録
        $this->app->singleton('contextual.log', function ($app) {
            return new ContextualLogService();
        });
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
