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
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username_id' => ['required', 'string', 'max:255', 'unique:users,username_id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
        
        // Check if the registration is for an administrator and require tenant name
        if ($request->boolean('is_admin')) {  // Change to boolean check for clarity
            $rules['tenant_name'] = ['required', 'string', 'max:255', 'unique:tenants,name'];
        }

        $request->validate($rules);

        // Create user
        $user = User::create([
            'name' => $request->name,
            'username_id' => $request->username_id,
            'password' => Hash::make($request->password),
        ]);

        // If registering as an admin, create the tenant record and associate it
        if ($request->boolean('is_admin')) {
            $tenantName = $request->tenant_name;
            $domain = $this->generateUniqueDomain($tenantName);

            $tenant = Tenant::create([
                'name' => $tenantName,
                'domain' => $domain,
            ]);

            // Associate the user with the newly created tenant
            $user->tenant_id = $tenant->id;
            $user->save();

            // Find the admin role and assign it to the user
            $adminRole = Role::findByName('admin');
            $user->assignRole($adminRole);

            Log::info('Admin user created and associated with tenant', ['user_id' => $user->id, 'tenant_id' => $tenant->id]);
        }

        Auth::login($user); // Log in the newly created user

        return redirect(route('dashboard'))->with('success', '新しいユーザーが正常に登録されました。');
    }

    /**
     * Generate a unique domain for the tenant.
     *
     * @param string $tenantName
     * @return string
     */
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
