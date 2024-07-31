<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stancl\Tenancy\Facades\Tenancy;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        Log::info('Displaying registration view');
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        Log::info('Registration request received', ['request' => $request->all()]);

        $request->validate([
            'name' => 'required|string|max:255',
            'username_id' => 'required|string|max:255|unique:users,username_id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        Log::info('Validation passed');

        // 現在のテナントIDを取得
        $currentTenantId = tenant('id'); // もしくは Tenancy::getTenant()->id;
        if (!$currentTenantId) {
            Log::error('Current tenant ID not found');
            return response()->json(['error' => 'Current tenant ID not found'], 400);
        }
        Log::info('Current tenant ID: ' . $currentTenantId);

        $user = User::create([
            'name' => $request->name,
            'username_id' => $request->username_id,
            'password' => Hash::make($request->password),
            'tenant_id' => $currentTenantId, // 動的に取得したテナントIDを設定
        ]);

        Log::info('User created', ['user' => $user]);

        event(new Registered($user));

        Auth::login($user);

        Log::info('User logged in', ['user' => $user]);

        return response()->json(['redirect' => route('dashboard')], 200);
    }
}
