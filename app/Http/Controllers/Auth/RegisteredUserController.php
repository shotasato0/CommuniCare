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

class RegisteredUserController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'username_id' => 'required|string|max:255|unique:users,username_id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
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
        ]);


        event(new Registered($user));

        return redirect()->route('dashboard')->with(['success' => 'ユーザー登録が完了しました。']);
    }
}
