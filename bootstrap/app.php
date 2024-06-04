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
        // 他のミドルウェア設定...
        $middleware->prepend(\Illuminate\Session\Middleware\StartSession::class);

        // Tenancy Middlewareの登録
        $middleware->prepend(\Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class);
        $middleware->prepend(\Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class);

        // カスタムミドルウェアの登録
        $middleware->prepend(\App\Http\Middleware\InitializeTenancyMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();