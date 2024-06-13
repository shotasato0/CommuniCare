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
use Illuminate\Support\Facades\Session;

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
        Log::info('ログインリクエストを受信しました', ['username_id' => $request->input('username_id')]);

        $request->validate([
            'username_id' => 'required|string',
            'password' => 'required|string',
        ]);

        Log::info('ユーザー名IDで認証を試みています: ' . $request->input('username_id'));

        // デバッグ情報の追加
        Log::info('現在のデータベース接続: ' . DB::connection('tenant')->getDatabaseName());

        $user = DB::connection('tenant')->table('users')->where('username_id', $request->input('username_id'))->first();
        Log::info('ユーザーのクエリ結果:', ['user' => $user]);

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            Log::warning('ユーザー名IDで認証に失敗しました: ' . $request->input('username_id'));
            return back()->withErrors([
                'username_id' => '提供された認証情報は当社の記録と一致しません。',
            ]);
        }

        Auth::loginUsingId($user->id);
        Log::info('ユーザー名IDで認証に成功しました: ' . $request->input('username_id'));

        $request->session()->regenerate();
        Log::info('ユーザーのセッションが再生成されました: ' . $request->input('username_id'));

        // セッションデータをログに出力
        Log::info('ログイン後のセッションデータ', ['session' => Session::all()]);

        Log::info('意図したURLにリダイレクトしています');
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
