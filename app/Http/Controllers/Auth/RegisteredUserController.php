<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\DatabaseManager;
use Stancl\Tenancy\Tenancy;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username_id' => ['required', 'string', 'max:255', 'unique:users,username_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($request->boolean('is_admin')) {
            $rules['tenant_name'] = ['required', 'string', 'max:255', 'unique:tenants,name'];
        }

        $request->validate($rules);

        $user = User::create([
            'name' => $request->name,
            'username_id' => $request->username_id,
            'password' => Hash::make($request->password),
        ]);

        if ($request->boolean('is_admin')) {
            $tenantName = $request->tenant_name;
            $domain = $this->generateUniqueDomain($tenantName);

            // テナントの作成
            $tenant = Tenant::create([
                'name' => $tenantName,
                'domain' => $domain,
                'database' => 'tenant_' . Str::slug($tenantName),
            ]);

            // テナントデータベースの作成
            $databaseName = 'tenant_' . $tenant->id;
            DB::statement("CREATE DATABASE IF NOT EXISTS `$databaseName`");

            // テナント用のマイグレーションを実行
            tenancy()->initialize($tenant);
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
                '--path' => 'database/migrations/tenant',
            ]);

            // テナントのコンテキストでユーザーを作成
            tenancy()->initialize($tenant);
            $user->tenant_id = $tenant->id;
            $user->save();

            $adminRole = Role::findByName('admin');
            $user->assignRole($adminRole);
        }

        Auth::login($user);

        return redirect(route('dashboard'))->with('success', '新しいユーザーが正常に登録されました。');
    }

    private function generateUniqueDomain(string $tenantName): string
    {
        $baseDomain = Str::slug($tenantName) . '.example.com';
        $domain = $baseDomain;
        $counter = 1;

        while (Tenant::where('domain', $domain)->exists()) {
            $domain = Str::slug($tenantName) . $counter . '.example.com';
            $counter++;
        }

        return $domain;
    }
}
