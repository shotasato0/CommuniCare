<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Tenant;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

   /**
 * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        // ドメインからテナントを特定
        $domain = $request->getHost();

        // 環境に応じてドメインからテナントIDを抽出
        $tenantDomainId = match(config('app.env')) {
            'local' => str_replace('.localhost', '', $domain),
            'production' => str_replace('.communi-care.jp', '', $domain),
            default => $domain
        };

        $tenant = Tenant::where('tenant_domain_id', $tenantDomainId)->first();

        if (!$tenant) {
            return back()->withErrors([
                'username_id' => '無効なドメインです。',
            ]);
        }

        // ベース認証情報
        $credentials = $request->only('username_id', 'password');
        // tenant_idを自動的に追加
        $credentials['tenant_id'] = $tenant->id;

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            session()->flash('success', 'ログインしました');
            return Inertia::location(route('dashboard'));
        }

        return back()->withErrors([
            'username_id' => '入力された情報が一致しません。',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // セッションからドメイン情報を取得し、セッションが存在しない場合はリクエストから取得
        $domain = session('tenant_domain', $request->getHost());

        return redirect('http://' . $domain . '/home')
            ->with('message', 'ログアウトしました');
    }
}
