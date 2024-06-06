<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Providers\RouteServiceProvider;
use Stancl\Tenancy\Resolvers\CachedTenantResolver;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        Log::info('Login request received', ['username_id' => $request->input('username_id')]);

        $request->validate([
            'username_id' => 'required|string',
            'password' => 'required|string',
        ]);

        Log::info('Attempting to authenticate user with username_id: ' . $request->input('username_id'));

        // デバッグ情報の追加
        Log::info('Current database connection: ' . DB::connection('tenant')->getDatabaseName());

        $user = DB::connection('tenant')->table('users')->where('username_id', $request->input('username_id'))->first();
        Log::info('User query result:', ['user' => $user]);

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            Log::warning('Authentication failed for username_id: ' . $request->input('username_id'));
            return back()->withErrors([
                'username_id' => 'The provided credentials do not match our records.',
            ]);
        }

        Auth::loginUsingId($user->id);
        Log::info('Authentication successful for username_id: ' . $request->input('username_id'));

        $request->session()->regenerate();

        Log::info('Session regenerated for user: ' . $request->input('username_id'));

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
