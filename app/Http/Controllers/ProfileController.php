<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
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
        $units = Unit::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('sort_order')
            ->get();

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
        'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
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

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // セッションからドメイン情報を取得し、セッションが存在しない場合はリクエストから取得
        $domain = session('tenant_domain', $request->getHost());

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Log::info('セッションが再生成されました');

        return Redirect::to('http://' . $domain . '/'); // 必要に応じて 'http://' を 'https://' に変更
    }

}
