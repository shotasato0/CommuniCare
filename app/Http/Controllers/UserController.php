<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Unit;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $tenantId = auth()->user()->tenant_id;  // 現在のユーザーのテナントIDを取得

    $users = User::where('tenant_id', $tenantId)->get();  // テナントIDでユーザーを絞り込み
    $units = Unit::where('tenant_id', $tenantId)->get();  // ユニットも同様にフィルタリング
    
    // 管理者ユーザーを取得
    $currentAdmin = User::role('admin')
        ->where('tenant_id', $tenantId)  // 管理者も同じテナント内のユーザーに限定
        ->first();
    $currentAdminId = $currentAdmin ? $currentAdmin->id : null;
    
    return Inertia::render('Users/Index', [
        'users' => $users,
        'units' => $units,
        'currentAdminId' => $currentAdminId,
    ]);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */

     public function show(User $user)
     {
         $user->load('unit'); // ユニット情報をロードする
         return Inertia::render('Users/Show', [
             'user' => $user,
             'units' => Unit::all(),
         ]);
     }


    public function editProfile(User $user)
    {
        $user->load('unit'); // ユニット情報をロードする
        return Inertia::render('Users/EditProfilePage', [
            'user' => $user,
            'units' => Unit::all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user' => $user,
            // 'units' => Unit::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'tel' => 'nullable|string|max:20',
        'email' => 'required|email|max:255',
        'unit_id' => 'nullable|exists:units,id',
    ]);

    $user->update($validatedData);

    return redirect()->route('users.editProfile', $user->id)
            ->with('success', 'プロフィールが更新されました。');
    }

    public function editIcon(User $user)
{
    $user->load('unit'); // ユニット情報をロードする
    return Inertia::render('Users/IconEdit', [
        'user' => $user,
        'icon' => $user->icon ? '/storage/' . $user->icon : null,
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
        return redirect()->route('users.editProfile', $user->id)
            ->with('success', 'プロフィール画像が更新されました。');
    } catch (\Exception $e) {
        // エラーが発生した場合はエラーメッセージを表示
        return redirect()->route('users.editProfile', $user->id)
            ->with('error', 'プロフィール画像の更新に失敗しました。');
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('users.index')->with('success', '職員が削除されました。');
    }

    // ユーザーの所属ユニットのforum_idを取得
    // app/Http/Controllers/UserController.php

public function getUserForumId(Request $request)
{
    $user = $request->user();

    // ユーザーがユニットに所属していない場合
    if (!$user->unit) {
        return response()->json(['error' => __('messages.user_not_in_unit')], 404);
    }

    // ユーザーが所属するユニットの forum_id を取得
    $forumId = optional($user->unit->forum)->id;

    if (!$forumId) {
        return response()->json(['error' => __('messages.forum_not_found')], 404);
    }

    return response()->json(['forum_id' => $forumId]);
}


}
