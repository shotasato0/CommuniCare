<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;
use Stancl\Tenancy\Tenancy;
use Illuminate\Support\Facades\Log;

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
    if ($request->is('login') || $request->is('register') || $request->is('password/*')) {
        return $next($request);
    }

    try {
        Log::info('Resolving tenant for domain: ' . $request->getHost());
        $tenant = $this->tenantResolver->resolve($request);
        if ($tenant) {
            Log::info('Tenant identified: ' . $tenant->id);
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
