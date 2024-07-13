<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;

class TenantController extends Controller
{
    public function showRegistrationForm()
    {
        return inertia('Auth/TenantRegister');
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

        // テナントIDをセッションに保存
        session(['tenant_id' => $tenant->id]);

        // テナント初期化後にリダイレクト
        return redirect()->to('http://' . $domain . '/register'); // リダイレクト先を新しいドメインに設定
    }
}
