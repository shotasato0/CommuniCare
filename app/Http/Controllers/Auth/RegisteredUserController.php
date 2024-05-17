<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;

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

            $tenant = Tenant::create([
                'name' => $tenantName,
                'domain' => $domain,
            ]);

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
