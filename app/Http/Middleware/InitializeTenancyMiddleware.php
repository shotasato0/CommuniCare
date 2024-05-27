<?php

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Tenancy;
use Illuminate\Support\Facades\Auth;
use App\Resolvers\CustomCachedTenantResolver;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException;
use Illuminate\Support\Facades\Log;

class InitializeTenancyMiddleware
{
    protected $tenancy;
    protected $tenantResolver;

    public function __construct(Tenancy $tenancy, CustomCachedTenantResolver $tenantResolver)
    {
        $this->tenancy = $tenancy;
        $this->tenantResolver = $tenantResolver;
    }

    public function handle($request, Closure $next)
    {
        Log::info('InitializeTenancyMiddleware::handle called for URL: ' . $request->url());

        try {
            $tenant = $this->tenantResolver->resolve($request);
            Log::info('Tenant identified: ' . $tenant->id);
            $this->tenancy->initialize($tenant);

            return $next($request);
        } catch (TenantCouldNotBeIdentifiedException $e) {
            Log::error('Tenant could not be identified: ' . $e->getMessage());
            Auth::logout();
            return redirect()->route('login')->with('error', 'テナントが見つかりません');
        }
    }
}
