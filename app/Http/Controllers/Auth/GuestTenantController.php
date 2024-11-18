<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class GuestTenantController extends Controller
{
    public function redirectToGuestTenant()
    {
        // ゲスト用テナントのドメインを指定
        $guestDomain = 'guestdemo.localhost';

        // ゲスト用ドメインが存在するか確認
        $domain = Domain::where('domain', $guestDomain)->first();
        if (!$domain) {
            abort(404, 'ゲスト用テナントが存在しません。');
        }

        // テナントの初期化
        $tenant = Tenant::find($domain->tenant_id);
        tenancy()->initialize($tenant);

        // セッションのドメインを動的に設定
        Config::set('session.domain', $guestDomain);

        // クッキーの設定（必要に応じて）
        $cookie = Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $guestDomain, false, true, false, 'Lax');
        Cookie::queue($cookie);

        // テナントIDをセッションに保存（任意）
        session(['tenant_id' => $tenant->id]);

        // ゲスト用テナントにリダイレクト
        return Inertia::location('http://' . $guestDomain . '/home');
    }
}
