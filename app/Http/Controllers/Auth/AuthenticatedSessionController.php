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
    Log::info('ログインリクエスト受信', ['username_id' => $request->input('username_id')]);

    $request->validate([
        'username_id' => 'required|string',
        'password' => 'required|string',
    ]);

    Log::info('ユーザー認証試行中: ' . $request->input('username_id'));

    // デバッグ情報の追加
    Log::info('現在のデータベース接続: ' . DB::connection('tenant')->getDatabaseName());

    $user = DB::connection('tenant')->table('users')->where('username_id', $request->input('username_id'))->first();
    Log::info('ユーザークエリ結果:', ['user' => $user]);

    if (!$user || !Hash::check($request->input('password'), $user->password)) {
        Log::warning('認証失敗: ' . $request->input('username_id'));
        return back()->withErrors([
            'username_id' => '入力された資格情報が記録と一致しません。',
        ]);
    }

    Auth::loginUsingId($user->id);
Log::info('認証成功: ' . $request->input('username_id'));

$request->session()->regenerate();
Log::info('セッション再生成後のユーザー: ' . $request->input('username_id'));
Log::info('ログイン後の現在のセッションID:', ['id' => Session::getId()]);

// セッションにユーザーIDを設定する
$request->session()->put('user_id', $user->id);
Log::info('セッションにユーザーIDを設定しました: ' . $user->id);

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
