<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class InitializeTenancyCustom extends InitializeTenancyByDomain
{
    public function handle($request, Closure $next)
    {
        // テナント識別をスキップするパス
        $excludedPaths = [
            '/',
            'tenant/login',
            'tenant/register',
            'guest/login',
            // 他にもスキップしたいパスを追加
        ];

        // 現在のパスがスキップ対象であればテナント識別をスキップ
        if (in_array($request->path(), $excludedPaths)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
