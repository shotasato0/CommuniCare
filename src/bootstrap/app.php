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
use App\Facades\Logs;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // 環境に応じたテナントドメインをconfigから取得（config:cacheと整合）
            $env = config('app.env');
            
            // テスト環境ではドメイン制限なしでルートを登録（InitializeTenancyByDomainはスキップ）
            if ($env === 'testing') {
                if (file_exists(base_path('routes/tenant.php'))) {
                    Route::middleware([
                        'web',
                        SetSessionDomain::class,
                    ])
                    ->group(base_path('routes/tenant.php'));
                }
            } else {
                $tenantDomain = match ($env) {
                    'local'      => config('guest.domains.local'),
                    'staging'    => config('guest.domains.staging'),
                    'production' => config('guest.domains.production'),
                    default      => config('guest.domains.production'),
                };

                if ($tenantDomain && file_exists(base_path('routes/tenant.php'))) {
                    Route::domain($tenantDomain)
                        ->middleware([
                            'web',
                            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
                            SetSessionDomain::class,
                        ])
                        ->group(base_path('routes/tenant.php'));
                }
            }
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            SetSessionDomain::class,
            AuthenticateSession::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            // テナント初期化はドメイン限定のルートグループ側で付与する
            SetTenantCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // すべての例外をログに記録（reportメソッドは常に呼び出される）
        $exceptions->report(function (\Throwable $e) {
            Logs::error('Unhandled exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);
            // 親クラスのreportメソッドも呼び出す
            return false; // falseを返すと、デフォルトのreport処理も実行される
        });
        
        // カスタム例外のハンドリング
        $exceptions->render(function (AuthenticationException $e) {
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $env = config('app.env');
            $guestDomain = match ($env) {
                'local'      => config('guest.domains.local'),
                'staging'    => config('guest.domains.staging'),
                'production' => config('guest.domains.production'),
                default      => config('guest.domains.production'),
            };

            $isGuestDomain = $guestDomain && request()->getHost() === $guestDomain;
            if ($isGuestDomain) {
                return redirect()->to(route('guest.login.view', ['expired' => 1]));
            }
            return redirect()->guest(route('login'));
        });
        $exceptions->render(function (TenantViolationException $e) {
            Log::critical('テナント境界違反が発生しました', $e->getLogContext());
            
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'error' => $e->getUserMessage(),
                    'code' => 'TENANT_VIOLATION'
                ], 403);
            } else {
                // HTMLレスポンス: リダイレクトでエラーメッセージを表示
                return redirect()->back()->with('error', $e->getUserMessage());
            }
        });

        $exceptions->render(function (PostOwnershipException $e) {
            Log::warning('投稿所有権違反が発生しました', $e->getLogContext());
            
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'error' => $e->getUserMessage(),
                    'code' => 'POST_OWNERSHIP_VIOLATION'
                ], 403);
            } else {
                // HTMLレスポンス: リダイレクトでエラーメッセージを表示
                return redirect()->back()->with('error', $e->getUserMessage());
            }
        });
    })->create();
