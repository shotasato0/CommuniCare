<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        // Log the incoming request data
        Log::info('Login request received', ['request' => $request->all()]);

        $request->validate([
            'username_id' => 'required|string',
            'password' => 'required|string',
        ]);

        Log::info('Attempting to authenticate user with username_id: ' . $request->input('username_id'));

        // Authenticate the user using username_id and password
        if (!Auth::attempt(['username_id' => $request->input('username_id'), 'password' => $request->input('password')])) {
            Log::info('Authentication failed for username_id: ' . $request->input('username_id'));
            return back()->withErrors([
                'username_id' => 'The provided credentials do not match our records.',
            ]);
        }

        Log::info('Authentication successful for username_id: ' . $request->input('username_id'));

        // テナント識別を再確認
        $tenant = app(CachedTenantResolver::class)->resolve($request);
        if ($tenant) {
            // データベース接続情報を設定
            $databaseName = $tenant->database;
            config(['database.connections.tenant.database' => $databaseName]);
            DB::purge('tenant');  // キャッシュされた接続をクリア
            DB::reconnect('tenant');  // 新しい接続を確立
            Log::info('Connected to tenant database: ' . $databaseName);
        } else {
            Log::info('Tenant not found for username_id: ' . $request->input('username_id'));
        }

        $request->session()->regenerate();
        Log::info('Session regenerated for user: ' . $request->input('username_id'));

        Log::info('Current database after reconnect: ' . DB::connection('tenant')->getDatabaseName());

        // セッションデータの確認ログ
        Log::info('Session Data After Login:', ['session' => session()->all()]);
        Log::info('User ID After Login:', ['user_id' => auth()->id()]);

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
