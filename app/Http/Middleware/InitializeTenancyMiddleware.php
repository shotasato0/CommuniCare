<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;
use Stancl\Tenancy\Tenancy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class InitializeTenancyMiddleware
{
    protected $tenancy;
    protected $tenantResolver;

    public function __construct(Tenancy $tenancy, CachedTenantResolver $tenantResolver)
    {
        $this->tenancy = $tenancy;
        $this->tenantResolver = $tenantResolver;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('InitializeTenancyMiddleware::handle が呼び出されました。URL: ' . $request->fullUrl());

        try {
            Log::info('ドメインに対してテナントを解決しています: ' . $request->getHost());
            $tenant = $this->tenantResolver->resolve($request);
            if ($tenant) {
                Log::info('テナントが特定されました: ' . $tenant->id);
                
                // データベース接続情報を設定
                $databaseName = $tenant->database;
                Log::info('テナントデータベース名: ' . $databaseName);
                
                config(['database.connections.tenant.database' => $databaseName]);
        
                DB::purge('tenant');  // キャッシュされた接続をクリア
                DB::reconnect('tenant');  // 新しい接続を確立
        
                Log::info('テナントデータベースに接続しました: ' . $databaseName);
                
                $this->tenancy->initialize($tenant);
            } else {
                Log::info('テナントが特定されませんでした');
            }
        } catch (\Exception $e) {
            Log::error('テナントの識別に失敗しました: ' . $e->getMessage());
            abort(404, 'テナントが見つかりません');
        }

        // テナントの初期化をログに記録
        $databaseName = DB::connection('tenant')->getDatabaseName();
        Log::info('初期化後のデータベース接続: ' . $databaseName);

        return $next($request);
    }
}
