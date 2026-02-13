<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\Log;
use App\Models\Unit;
use Illuminate\Validation\Rule;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Register', [
            'units' => Unit::where('tenant_id', tenant('id'))
                        ->orderBy('name')
                        ->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('tenant_id', tenant('id'));
                })
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'unit_id' => 'required|exists:units,id',
        ]);

        // 現在のテナントIDを取得
        $currentTenantId = tenant('id'); // もしくは Tenancy::tenant()->id;
        if (!$currentTenantId) {
            Log::error('Current tenant ID not found');
            return response()->json(['error' => 'Current tenant ID not found'], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'username_id' => $request->username_id,
            'password' => Hash::make($request->password),
            'tenant_id' => $currentTenantId,
            'unit_id' => $request->unit_id,
        ]);

        event(new Registered($user));

        return redirect()->route('users.index')->with(['success' => '職員の登録が完了しました。']);
    }
}
