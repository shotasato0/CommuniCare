<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Stancl\Tenancy\Tenancy;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    protected $tenancy;
    protected $tenantResolver;

    public function __construct(Tenancy $tenancy, DomainTenantResolver $tenantResolver)
    {
        $this->tenancy = $tenancy;
        $this->tenantResolver = $tenantResolver;
    }

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    // ここでテナント識別を再確認
    $tenant = app(CachedTenantResolver::class)->resolve($request);
    if ($tenant) {
        // データベース接続情報を設定
        $databaseName = $tenant->database;
        config(['database.connections.tenant.database' => $databaseName]);
        DB::purge('tenant');  // キャッシュされた接続をクリア
        DB::reconnect('tenant');  // 新しい接続を確立
    }

    $request->session()->regenerate();

    return redirect()->intended(RouteServiceProvider::HOME);
}


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function username(): string
    {
        return 'username_id';
    }
}
