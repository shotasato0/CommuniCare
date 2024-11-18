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
        $credentials = $request->only('username_id', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // 現在のリクエストからホスト名を取得し、セッションに保存
            $domain = $request->getHost();
            session(['tenant_domain' => $domain]);

            // セッションにリロードフラグを設定
            session()->flash('reload_page', true);

            return inertia::location('dashboard');
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

        return redirect('http://' . $domain . '/home');
    }
}
