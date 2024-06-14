<?php

namespace App\Resolvers;

use App\Models\Tenant as TenantModel;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomCachedTenantResolver extends CachedTenantResolver
{
    protected int $cacheTtl = 3600;

    public function resolve(...$args): Tenant
    {
        Log::info('CustomCachedTenantResolver::resolveが呼び出されました');

        if (!isset($args[0])) {
            throw new \InvalidArgumentException('リクエストオブジェクトがありません');
        }

        $request = $args[0];
        $cacheKey = $this->getCacheKey($request);
        Log::info('キャッシュキーでテナントを解決中: ' . $cacheKey);

        return $this->cache->remember($cacheKey, $this->cacheTtl, function () use ($request) {
            return $this->resolveWithoutCache($request);
        });
    }

    public function resolveWithoutCache(...$args): Tenant
    {
        Log::info('CustomCachedTenantResolver::resolveWithoutCacheが呼び出されました');

        $request = $args[0];
        $domain = $request->getHost();
        Log::info('ドメインでテナントを解決中: ' . $domain);

        try {
            $tenant = TenantModel::where('domain', $domain)->first();
            if (!$tenant) {
                Log::error('ドメインに対するテナントが見つかりませんでした: ' . $domain);
                throw new \Exception('テナントが見つかりません');
            }
            Log::info('テナントが解決されました: ' . $tenant->id);

            // データベース接続情報を設定
            $databaseName = $tenant->database; // データベース名を直接使用
            config(['database.connections.tenant.database' => $databaseName]);

            // デバッグ用ログを追加
            Log::info('テナントデータベース接続を設定中: ' . $databaseName);

            DB::purge('tenant');  // キャッシュされた接続をクリア
            DB::reconnect('tenant');  // 新しい接続を確立

            // デバッグ用ログを追加
            Log::info('テナントデータベース接続が設定されました: ' . $databaseName);

            return $tenant;
        } catch (\Exception $e) {
            Log::error('テナントの解決中にエラーが発生しました: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getCacheKey(...$args): string
    {
        Log::info('CustomCachedTenantResolver::getCacheKeyが呼び出されました');

        $request = $args[0];
        return 'tenant_' . $request->getHost();
    }

    public function getArgsForTenant(Tenant $tenant): array
    {
        return [$tenant->domain];
    }
}
