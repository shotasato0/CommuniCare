<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ここで他のミドルウェア設定を追加
        $middleware->prepend(\Illuminate\Session\Middleware\StartSession::class);
        $middleware->prepend(\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class);
        // $middleware->prepend(\App\Http\Middleware\EncryptCookies::class);

        // Tenancy Middlewareの登録
        $middleware->prepend(\Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class);
        $middleware->prepend(\App\Http\Middleware\InitializeTenancyMiddleware::class); // Custom Middleware
        $middleware->prepend(\Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class);

        // 追加ミドルウェア
        $middleware->prepend(\Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class);
        $middleware->prepend(\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class);
        $middleware->prepend(\Illuminate\Foundation\Http\Middleware\TrimStrings::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
