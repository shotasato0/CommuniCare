<?php

namespace App\Http\Middleware;

use App\Models\Tenant as TenantModel; // ここでEloquentモデルをインポート
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;

class CustomCachedTenantResolver extends CachedTenantResolver
{
    public function resolveWithoutCache(...$args): Tenant
    {
        if (!isset($args[0])) {
            throw new \InvalidArgumentException('The request object is missing.');
        }

        $request = $args[0];
        $domain = $request->getHost();

        // Eloquentモデルを使用してテナントを検索
        $tenant = TenantModel::where('domain', $domain)->firstOrFail();

        return $tenant;
    }

    public function getArgsForTenant(Tenant $tenant): array
    {
        return [$tenant->domain];
    }
}
