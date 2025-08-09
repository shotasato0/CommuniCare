<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Http\Request;

class GuestLoginController extends Controller
{
    /**
     * ゲストとしてログイン
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function loginAsGuest(Request $request)
{
    // 現在のセッションIDを取得
    $initialSessionId = session()->getId();
    $guestPassword = 'temporary_password'; // ゲスト用の一時パスワード
    $guestUsernameId = 'guest' . $initialSessionId . bin2hex(random_bytes(3)); // 一意なゲストID
    $tenantId = tenancy()->tenant->id; // テナントIDを取得

    // ゲストユーザーを作成または更新
    $guestUser = User::updateOrCreate(
        [
            'username_id' => $guestUsernameId,
            'tenant_id' => $tenantId,
        ],
        [
            'name' => 'Guest',
            'password' => Hash::make($guestPassword),
            'guest_session_id' => $initialSessionId,
        ]
    );


    // ゲストユーザーとしてログイン
    Auth::login($guestUser);

    // ログイン後の新しいセッションIDを取得
    $newSessionId = session()->getId();

    // ゲストユーザーのセッションIDを更新
    $guestUser->update(['guest_session_id' => $newSessionId]);

    // フォーラムページに遷移
    return redirect()->route('forum.index')->with('success', 'ゲストとしてログインしました');
}


    /**
     * ゲストユーザーとしてログアウト
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logoutGuest()
{
    // 現在のセッションIDを取得
    $sessionId = session()->getId();

    // セッションIDでゲストユーザーを検索・削除
    $guestUser = User::where('guest_session_id', $sessionId)->first();

    if ($guestUser) {
        $guestUser->delete();
    } else {
        Log::warning("該当するゲストユーザーが見つかりません: セッションID: {$sessionId}");
    }

    // セッション無効化とトークン再生成を最後に実行
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    // ホーム画面にリダイレクト
    return redirect('/')->with('message', 'ログアウトしました');
}

}
