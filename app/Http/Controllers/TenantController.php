<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Inertia\Inertia;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class TenantController extends Controller
{
    public function showRegistrationForm()
    {
        return Inertia::render('Auth/TenantRegister');
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'tenant_name' => 'required|string|max:255',
        ]);

        // テナントの作成
        $tenant = Tenant::create([
            'name' => $validatedData['tenant_name'],
        ]);

        // ドメインの自動生成
        $domain = strtolower(preg_replace('/[^\x20-\x7E]/', '', str_replace(' ', '-', $validatedData['tenant_name']))) . '.localhost';
        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => $domain,
        ]);

        // テナント登録後にそのテナントのデータベースに切り替える
        tenancy()->initialize($tenant);

        // セッションのドメインを動的に設定
        Config::set('session.domain', $domain);

        // クッキーの設定
        $cookie = Cookie::make('XSRF-TOKEN', csrf_token(), 120, '/', $domain, false, true, false, 'Lax');
        Cookie::queue($cookie);

        // テナントIDをセッションに保存
        session(['tenant_id' => $tenant->id]);

        // テナント初期化後にリダイレクト
        return Inertia::location('http://' . $domain . '/home');
    }
}
