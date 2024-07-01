<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function showRegistrationForm()
    {
        return inertia('Auth/TenantRegister');
    }

    public function register(Request $request)
    {
        $request->validate([
            'tenant_name' => 'required|string|max:255',
        ]);

        $tenant = Tenant::create([
            'name' => $request->tenant_name,
        ]);

        session(['tenant_id' => $tenant->id]);

        return redirect()->route('register');
    }
}

