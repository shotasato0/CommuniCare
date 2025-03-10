<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Application;
use Inertia\Inertia;
use App\Models\User;
class TenantHomeController extends Controller
{
    public function index()
    {
        // セッションからドメイン名を取得
        $domain = Session::get('tenant_domain', '不明なドメイン');
        
        // テナントIDを取得
        $tenantId = tenant('id');

        // このテナントに管理者が存在するかを確認
        $adminExists = User::role('admin')
            ->where('tenant_id', $tenantId)
            ->exists();

        return Inertia::render('TenantHome', [
            'canLogin' => \Route::has('login'),
            'canRegister' => \Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'adminExists' => $adminExists,
        ]);
    }
}

