<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Stancl\Tenancy\Database\Models\Domain;
use Illuminate\Support\Facades\Log;

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

            // 設定が正しく行われたかをログに記録
            Log::info('Session domain set to: ' . Config::get('session.domain'));

            // クッキーの設定
            $cookie = Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $host, false, true, false, 'Lax');
            Cookie::queue($cookie);
        }

        return $next($request);
    }
}

