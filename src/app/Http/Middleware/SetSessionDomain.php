<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

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
        // 現在のリクエストのホスト名を取得
        $currentHost = $request->getHost();

        // リクエストホストに基づいて適切なセッションドメインを解決
        $dynamicDomain = $this->resolveDomain($currentHost);

        // セッションドメインを設定
        Config::set('session.domain', $dynamicDomain);

        // セッションドメインの設定をログ出力
        Log::info('SetSessionDomain: ' . $dynamicDomain);

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
        // communi-care.jp ドメイン（www含む）およびサブドメインを許可
        if (preg_match('/(^|.+\.)communi-care\.jp$/', $host)) {
            return $host;
        }

        // localhost の場合も、サブドメインごとにセッションを分離
        if (preg_match('/^.+\.localhost$/', $host)) {
            return $host; // サブドメインをそのまま返す
        }

        // デフォルトのセッションドメインを返す
        return config('session.domain', null);
    }
}
