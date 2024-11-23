<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GuestLoginController extends Controller
{

    public function loginAsGuest()
    {
        // セッションIDを取得して一意のゲスト識別子を生成
        $sessionId = session()->getId();

        $guestPassword = 'temporary_password';
        $guestUsernameId = 'guest' . $sessionId;

        // 現在のテナントのIDを取得
        $tenantId = tenancy()->tenant->id;

        // ゲストユーザーを作成または取得
        $guestUser = User::firstOrCreate(
            [
                'name' => 'Guest',
                'password' => Hash::make($guestPassword),
                'username_id' => $guestUsernameId,
                'tenant_id' => $tenantId, // tenant_id を明示的に設定
            ]
        );

        // ゲストユーザーとしてログイン
        Auth::login($guestUser);

        return redirect('/dashboard')->with('message', 'ゲストとしてログインしました！');
    }
}
