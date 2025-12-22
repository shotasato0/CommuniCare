<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

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

    // ロールを取得または作成
    $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    // ゲストユーザーにロールが割り当てられていない場合は割り当てる
    // リレーションを明示的にロードしてからチェック
    $guestUser->load('roles');
    if ($guestUser->roles->isEmpty()) {
        $guestUser->assignRole($userRole);
        // ロールを再読み込み
        $guestUser->refresh();
        Log::info('ゲストユーザーにロールを割り当てました', [
            'user_id' => $guestUser->id,
            'role' => 'user',
            'roles' => $guestUser->getRoleNames()->toArray(),
        ]);
    }

    // ゲストユーザーとしてログイン
    Auth::login($guestUser);

    // ログイン後の新しいセッションIDを取得
    $newSessionId = session()->getId();

    // ゲストユーザーのセッションIDを更新
    $guestUser->update(['guest_session_id' => $newSessionId]);

    // ダッシュボードページに遷移
    return redirect()->route('dashboard')->with('success', 'ゲストとしてログインしました');
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

    // セッションIDでゲストユーザーを検索
    $guestUser = User::where('guest_session_id', $sessionId)->first();

    // まずログアウトとセッション無効化を行う（ミドルウェアとの競合回避）
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    // 認証状態を切った後にクリーンアップ（関連FKに備えて失敗時は安全にフォールバック）
    if ($guestUser) {
        try {
            $guestUser->delete();
        } catch (\Throwable $e) {
            // 外部キー制約等で削除できない場合はゲストフラグのみ解除して継続
            Log::warning('ゲストユーザー削除に失敗。guest_session_idを解除して継続', [
                'user_id' => $guestUser->id,
                'error' => $e->getMessage(),
            ]);
            $guestUser->forceFill(['guest_session_id' => null])->save();
        }
    } else {
        Log::warning("該当するゲストユーザーが見つかりません: セッションID: {$sessionId}");
    }

    // ホーム画面にリダイレクト
    return redirect('/')->with('message', 'ログアウトしました');
}

}
