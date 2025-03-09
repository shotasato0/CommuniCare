<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Stancl\Tenancy\Database\Models\Domain;

class SetTenantCookie
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        // 共通データベースからドメインを検索する
        $domain = Domain::where('domain', $host)->first();

        if ($domain) {
            // セッションのドメインを動的に設定
            Config::set('session.domain', $host);

            // クッキーの設定
            $cookie = Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $host, false, true, false, 'Lax');
            Cookie::queue($cookie);
        }

        return $next($request);
    }
}

