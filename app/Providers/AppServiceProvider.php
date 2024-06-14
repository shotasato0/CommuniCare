<?php

namespace App\Providers;

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Resolvers\CustomCachedTenantResolver;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    protected $booted = false; // クエリリスナー登録のフラグ

    public function register()
    {
        $this->app->singleton(CachedTenantResolver::class, CustomCachedTenantResolver::class);
    }

    public function boot()
{
    if (!$this->app->bound('query.listening')) {
        DB::listen(function ($query) {
            info("クエリを実行しています: {$query->sql} with バインディング: " . implode(', ', $query->bindings));
        });
        $this->app->instance('query.listening', true);
        info("クエリリスナーが登録されました");
    }
}


}

