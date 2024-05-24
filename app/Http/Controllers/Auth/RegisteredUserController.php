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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Database\DatabaseManager;
use Illuminate\Http\RedirectResponse;

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
            // 日本語をラテン文字に変換
            $databaseName = 'tenant_' . transliterator_transliterate('Any-Latin; Latin-ASCII', $tenantName);

            // テナントの作成
            $tenant = Tenant::create([
                'name' => $tenantName,
                'domain' => $domain,
                'database' => $databaseName,
            ]);

            // テナントデータベースの作成
            DB::statement("CREATE DATABASE IF NOT EXISTS $databaseName");

            config([
                'database.connections.tenant' => [
                    'driver' => 'mysql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => $databaseName,
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ],
            ]);

            // テナント用のマイグレーションを実行
            DB::purge('tenant');
            DB::reconnect('tenant');
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
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
