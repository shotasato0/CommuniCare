<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function showRegistrationForm()
    {
        return inertia('TenantRegister');
    }

    public function register(Request $request)
    {
        $request->validate([
            'tenant_name' => 'required|string|max:255',
        ]);

        Tenant::create([
            'name' => $request->tenant_name,
        ]);

        return redirect()->route('register'); // 新規登録ビューに遷移
    }
}
