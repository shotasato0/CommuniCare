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
        Log::info('InitializeTenancyMiddleware::handle called for URL: ' . $request->fullUrl());

        // 特定のルートをスキップ
        // if ($request->is('login') || $request->is('register') || $request->is('password/*')) {
        //     Log::info('Skipping tenant identification for route: ' . $request->path());
        //     return $next($request);
        // }

        try {
            Log::info('Resolving tenant for domain: ' . $request->getHost());
            $tenant = $this->tenantResolver->resolve($request);
            if ($tenant) {
                Log::info('Tenant identified: ' . $tenant->id);
                
                // データベース接続情報を設定
                $databaseName = $tenant->database;
                Log::info('Tenant database name: ' . $databaseName);
                
                config(['database.connections.tenant.database' => $databaseName]);
        
                DB::purge('tenant');  // キャッシュされた接続をクリア
                DB::reconnect('tenant');  // 新しい接続を確立
        
                Log::info('Connected to tenant database: ' . $databaseName);
                
                $this->tenancy->initialize($tenant);
            } else {
                Log::info('No tenant identified');
            }
        } catch (\Exception $e) {
            Log::error('Tenant identification failed: ' . $e->getMessage());
            abort(404, 'Tenant not found');
        }

        return $next($request);
    }
}
