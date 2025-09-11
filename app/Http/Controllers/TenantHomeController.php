<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Application;
use Inertia\Inertia;
use App\Models\User;
use Stancl\Tenancy\Database\Models\Domain;
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
            $tenantId = Domain::where('domain', $host)->value('tenant_id');
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

        // ゲスト表示条件: 専用パス or ゲストドメイン
        $isGuestHome = (
            request()->routeIs('guest.login.view') ||
            request()->is('guest/login') ||
            $isGuestDomain
        );

        return Inertia::render('TenantHome', [
            'canLogin' => \Route::has('login'),
            'canRegister' => \Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'adminExists' => $adminExists,
            'isGuestHome' => $isGuestHome,
        ]);
    }
}
