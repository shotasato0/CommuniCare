<?php

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException;

class InitializeTenancyMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            tenancy()->initializeTenancy();
            return $next($request);
        } catch (TenantCouldNotBeIdentifiedException $e) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'テナントが見つかりません');
        }
    }
}
