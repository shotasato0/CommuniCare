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
    Log::info('Login request received', ['username_id' => $request->input('username_id')]);

    $request->validate([
        'username_id' => 'required|string',
        'password' => 'required|string',
    ]);

    Log::info('Attempting to authenticate user with username_id: ' . $request->input('username_id'));

    if (!Auth::attempt(['username_id' => $request->input('username_id'), 'password' => $request->input('password')])) {
        Log::warning('Authentication failed for username_id: ' . $request->input('username_id'));
        return back()->withErrors([
            'username_id' => 'The provided credentials do not match our records.',
        ]);
    }

    Log::info('Authentication successful for username_id: ' . $request->input('username_id'));

    // セッションの再生成前のセッションID
    Log::info('Session ID before regenerate: ' . session()->getId());

    // セッションの再生成
    $request->session()->regenerate();

    // セッションの再生成後のセッションID
    Log::info('Session ID after regenerate: ' . session()->getId());

    // セッション情報の確認
    Log::info('Session data: ', ['session' => session()->all()]);

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
