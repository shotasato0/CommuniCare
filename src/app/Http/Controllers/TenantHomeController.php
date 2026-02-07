<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Application;
use Inertia\Inertia;
use App\Models\User;
use Stancl\Tenancy\Database\Models\Domain;
use Illuminate\Support\Facades\Cache;
class TenantHomeController extends Controller
{
    public function index()
    {
        // セッションからドメイン名を取得
        $domain = Session::get('tenant_domain', '不明なドメイン');
        
        // テナントIDを取得（tenancy 未初期化時のフォールバックを用意）
        $tenantId = tenant('id');
        if (!$tenantId) {
            $host = request()->getHost();
            // ホスト名の簡易バリデーション（Host ヘッダ汚染対策）
            if (is_string($host) && preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
                // ドメイン→テナントIDの解決を短時間キャッシュ
                $cacheKey = 'tenantIdByDomain:' . strtolower($host);
                $tenantId = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($host) {
                    return Domain::where('domain', $host)->value('tenant_id');
                });
            } else {
                $tenantId = null;
            }
        }

        // このテナントに管理者が存在するかを確認
        $adminExists = false;
        if ($tenantId) {
            $adminExists = User::role('admin')
                ->where('tenant_id', $tenantId)
                ->exists();
        }

        // ゲストデモ用ドメインかどうか
        $env = config('app.env');
        $guestDomain = match ($env) {
            'local'      => config('guest.domains.local'),
            'staging'    => config('guest.domains.staging'),
            'production' => config('guest.domains.production'),
            default      => config('guest.domains.production'),
        };
        $isGuestDomain = $guestDomain && request()->getHost() === $guestDomain;

        // ゲスト表示条件: ゲストドメインでのアクセス時のみ（パスの判定は行っていません）
        $isGuestHome = $isGuestDomain;

        return Inertia::render('TenantHome', [
            'canLogin' => \Route::has('login'),
            'canRegister' => \Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'adminExists' => $adminExists,
            'isGuestHome' => $isGuestHome,
            // セッション関連の表示制御用フラグ
            'isAuthenticated' => \Illuminate\Support\Facades\Auth::check(),
            'sessionExpired' => (bool) request()->boolean('expired'),
        ]);
    }
}
