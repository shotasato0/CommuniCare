<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class GuestTenantController extends Controller
{
    public function redirectToGuestTenant()
    {
        // 環境に基づいてゲストドメインを切り替える
        $guestDomain = match (config('app.env')) {
            'local' => env('GUEST_DOMAIN_LOCAL', 'guestdemo.localhost'),
            'staging' => env('GUEST_DOMAIN_STAGING', 'guestdemo.staging.communi-care.jp'),
            default => env('GUEST_DOMAIN_PRODUCTION', 'guestdemo.communi-care.jp'),
        };

        // ゲスト用ドメインが存在するか確認
        $domain = Domain::where('domain', $guestDomain)->first();
        if (!$domain) {
            abort(404, 'ゲスト用テナントが存在しません。');
        }

        // テナントの初期化
        $tenant = Tenant::find($domain->tenant_id);
        if (!$tenant) {
            abort(404, 'テナントが見つかりません。');
        }
        tenancy()->initialize($tenant);

        // セッションのドメインを設定
        Cookie::queue(Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $guestDomain, false, true, false, 'Lax'));
        session(['tenant_id' => $tenant->id]);

        // 環境に応じてHTTP/HTTPSを切り替えてリダイレクト
        $protocol = config('app.env') === 'local' ? 'http' : 'https';
        return Inertia::location($protocol . '://' . $guestDomain . '/');
    }

    // 中央→テナントへのフルリダイレクト（Inertia::location）をconfigベースで生成
    public function redirect()
    {
        $env = config('app.env');
        $domain = match ($env) {
            'local'      => config('guest.domains.local'),
            'staging'    => config('guest.domains.staging'),
            'production' => config('guest.domains.production'),
            default      => config('guest.domains.production'),
        };

        $scheme = config('guest.protocol', $env === 'local' ? 'http' : 'https');
        $port   = match ($env) {
            'local'      => config('guest.ports.local'),
            'staging'    => config('guest.ports.staging'),
            'production' => config('guest.ports.production'),
            default      => null,
        };

        if (empty($domain)) {
            Log::error('ゲストドメイン設定が未定義です', ['env' => $env]);
            abort(500, 'ゲストドメイン設定が未定義です。');
        }

        $host = $domain . ($port ? ":{$port}" : '');
        $url  = "{$scheme}://{$host}/guest/login";

        Log::info('ゲスト遷移URLを生成', compact('env', 'domain', 'port', 'url'));

        try {
            return Inertia::location($url);
        } catch (\Throwable $e) {
            Log::error('Inertia::location で例外が発生したため away() にフォールバック', [
                'message' => $e->getMessage(),
            ]);
            return redirect()->away($url);
        }
    }
}
