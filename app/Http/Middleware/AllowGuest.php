<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowGuest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 認証されていない場合でも、ゲストセッションを許可
        if (session('guest', false)) {
            return $next($request);
        }

        // 通常の認証状態も許可
        if (auth()->check()) {
            return $next($request);
        }

        // 上記以外の場合はログインページへリダイレクト
        return redirect()->route('login');
    }
}
