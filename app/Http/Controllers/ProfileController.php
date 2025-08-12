<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Unit;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        // 現在のテナントに属する部署のみを取得
        $units = Unit::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('sort_order')
            ->get();

        // プロフィール編集ページを表示
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'units' => $units,  // フィルタリングされた部署データ
            'user' => $request->user(),
        ]);
    }

    public function updateIcon(Request $request)
{
    // バリデーション
    $request->validate([
        'icon' => 'required|image:allow_svg|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
    ], [
        'icon.max' => '画像のサイズが大きすぎます。4MB以下にしてください。',
    ]);

    try {
        // ファイルを取得
    $file = $request->file('icon');

    // 一意のファイル名を生成
    $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

    // ファイルを保存（共通の 'icons' ディレクトリに保存）
    $path = $file->storeAs('icons', $fileName, 'public');

    // 既存のアイコンを削除（必要に応じて）
    if ($request->user()->icon) {
        Storage::disk('public')->delete($request->user()->icon);
    }

        // データベースに新しいパスを保存
        $user = $request->user();
        $user->icon = 'icons/' . $fileName;
        $user->save();
        // アイコン編集が完了したらユーザープロフィールページにリダイレクト
        return redirect()->route('profile.edit')
            ->with('success', 'プロフィール画像が更新されました。');
    } catch (\Exception $e) {
        // エラーが発生した場合はエラーメッセージを表示
        return redirect()->route('profile.edit')
            ->with('error', 'プロフィール画像の更新に失敗しました。');
    }
}

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // ProfileUpdateRequestのバリデーションは自動的に実行されます
        // 追加のバリデーションは不要です

        // ユーザーのプロフィール情報を更新
        $request->user()->fill($request->validated());

        // メールアドレスが変更された場合、verified_atをnullにする
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // ユーザー情報を保存
        $request->user()->save();

        // プロフィール編集ページにリダイレクト
        return Redirect::route('profile.edit')->with('success', 'プロフィールが更新されました。');
    }

    /**
     * Delete the user's account.
     */
    // ユーザーのアカウントを削除
    public function destroy(Request $request): RedirectResponse
    {
        // パスワードのバリデーション
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // ユーザー情報を取得
        $user = $request->user();

        // セッションからドメイン情報を取得し、セッションが存在しない場合はリクエストから取得
        $domain = session('tenant_domain', $request->getHost());

        // ログアウト
        Auth::logout();

        // ユーザーを削除
        $user->delete();

        // セッションを無効化し、新しいトークンを生成
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログを記録
        Log::info('セッションが再生成されました');

        // ドメインにリダイレクト
        return Redirect::to('http://' . $domain . '/');
    }

}
