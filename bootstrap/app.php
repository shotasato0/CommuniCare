<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use App\Http\Middleware\InitializeTenancyCustom;
use App\Http\Middleware\SetTenantCookie;
use App\Http\Middleware\SetSessionDomain;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\PostOwnershipException;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            SetSessionDomain::class,
            AuthenticateSession::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            InitializeTenancyCustom::class,
            SetTenantCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // カスタム例外のハンドリング
        $exceptions->render(function (TenantViolationException $e) {
            Log::critical('テナント境界違反が発生しました', $e->getLogContext());
            
            return response()->json([
                'error' => $e->getUserMessage(),
                'code' => 'TENANT_VIOLATION'
            ], 403);
        });

        $exceptions->render(function (PostOwnershipException $e) {
            Log::warning('投稿所有権違反が発生しました', $e->getLogContext());
            
            return response()->json([
                'error' => $e->getUserMessage(),
                'code' => 'POST_OWNERSHIP_VIOLATION'
            ], 403);
        });
    })->create();
