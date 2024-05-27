<?php

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Tenancy;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\CustomCachedTenantResolver;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException;

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
        try {
            $tenant = $this->tenantResolver->resolve($request);
            $this->tenancy->initialize($tenant);

            return $next($request);
        } catch (TenantCouldNotBeIdentifiedException $e) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'テナントが見つかりません');
        }
    }
}
