<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\CustomCachedTenantResolver;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(CachedTenantResolver::class, CustomCachedTenantResolver::class);
    }

    public function boot()
    {
        DB::listen(function ($query) {
            info("Executing query: {$query->sql} with bindings: " . implode(', ', $query->bindings));
        });
    }
}
