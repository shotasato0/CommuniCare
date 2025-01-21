<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class SetSessionDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 現在のホストを取得
        $currentHost = $request->getHost();

        // ドメインを動的に設定（例: サブドメインの設定を抽出）
        $dynamicDomain = $this->resolveDomain($currentHost);

        // セッションドメインを動的に設定
        Config::set('session.domain', $dynamicDomain);

        return $next($request);
    }

    /**
     * ドメインを解析して適切なセッションドメインを返す
     *
     * @param string $host
     * @return string|null
     */
    protected function resolveDomain(string $host): ?string
    {
        if (strpos($host, 'guestdemo.communi-care.jp') !== false) {
            return 'guestdemo.communi-care.jp';
        }

        // デフォルト値を返す
        return config('session.domain', null);
    }
}
