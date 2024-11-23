<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Http\Request;

class GuestLoginController extends Controller
{

    public function loginAsGuest(Request $request)
{
    $sessionId = session()->getId();
    $guestPassword = 'temporary_password';
    $guestUsernameId = 'guest' . $sessionId . bin2hex(random_bytes(3));
    $tenantId = tenancy()->tenant->id;

    $guestUser = User::updateOrCreate(
        [
            'username_id' => $guestUsernameId,
            'tenant_id' => $tenantId,
        ],
        [
            'name' => 'Guest',
            'password' => Hash::make($guestPassword),
            'guest_session_id' => $sessionId,
        ]
    );

    Auth::login($guestUser);

    return Inertia::render('Dashboard', [
        'auth' => [
            'user' => Auth::user(),
        ],
        'isGuest' => true,
        'guestSessionId' => $guestUser->guest_session_id,
    ])->with('message', 'ゲストとしてログインしました！');
}



    public function logoutGuest()
    {
        $sessionId = session()->getId();

        // ゲストデータを削除
        User::where('guest_session_id', $sessionId)->delete();

        // セッション削除
        Auth::logout();
        session()->invalidate();

        return redirect('/')->with('message', '操作内容がリセットされました。');
    }
}
