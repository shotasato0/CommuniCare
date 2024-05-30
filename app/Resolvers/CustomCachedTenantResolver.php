<?php

namespace App\Resolvers;

use App\Models\Tenant as TenantModel;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomCachedTenantResolver extends CachedTenantResolver
{
    // キャッシュの有効期限（秒）
    protected int $cacheTtl = 3600;

    public function resolve(...$args): Tenant
    {
        Log::info('CustomCachedTenantResolver::resolve called');

        if (!isset($args[0])) {
            throw new \InvalidArgumentException('The request object is missing.');
        }

        $request = $args[0];
        $cacheKey = $this->getCacheKey($request);
        Log::info('Resolving tenant for cache key: ' . $cacheKey);

        return $this->cache->remember($cacheKey, $this->cacheTtl, function () use ($request) {
            return $this->resolveWithoutCache($request);
        });
    }

    public function resolveWithoutCache(...$args): Tenant
{
    Log::info('CustomCachedTenantResolver::resolveWithoutCache called');

    $request = $args[0];
    $domain = $request->getHost();
    Log::info('Resolving tenant for domain: ' . $domain);

    $tenant = TenantModel::where('domain', $domain)->firstOrFail();
    Log::info('Tenant resolved: ' . $tenant->id);

    // データベース接続情報を設定
    $databaseName = $tenant->database; // データベース名を直接使用
    config(['database.connections.tenant.database' => $databaseName]);
    DB::purge('tenant');  // キャッシュされた接続をクリア
    DB::reconnect('tenant');  // 新しい接続を確立

    return $tenant;
}



    public function getCacheKey(...$args): string
    {
        Log::info('CustomCachedTenantResolver::getCacheKey called');

        $request = $args[0];
        return 'tenant_' . $request->getHost();
    }

    public function getArgsForTenant(Tenant $tenant): array
    {
        return [$tenant->domain];
    }
}
