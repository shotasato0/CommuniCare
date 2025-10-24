<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Requests\User\UserIconUpdateRequest;
use Inertia\Inertia;
use App\Models\Unit;
use App\Models\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $tenantId = Auth::user()->tenant_id;  // 現在のユーザーのテナントIDを取得

    $users = User::where('tenant_id', $tenantId)->get();  // テナントIDでユーザーを絞り込み
    $units = Unit::where('tenant_id', $tenantId)->orderBy('sort_order')->get(); // 並び順を保存
    
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
    public function update(UserUpdateRequest $request, User $user)
    {
        $user->update($request->validated());

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

public function updateIcon(UserIconUpdateRequest $request)
{
    try {
        $file = $request->file('icon');
        $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('icons', $fileName, 'public');

        if ($request->user()->icon) {
            Storage::disk('public')->delete($request->user()->icon);
        }

        $user = $request->user();
        $user->icon = 'icons/' . $fileName;
        $user->save();

        return redirect()->route('users.editProfile', $user->id)
            ->with('success', 'プロフィール画像が更新されました。');
    } catch (\Exception $e) {
        return redirect()->route('users.editProfile', $user->id)
            ->with('error', 'プロフィール画像の更新に失敗しました。');
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $currentUser = Auth::user();

        // 権限チェック: 管理者のみ（IDE/環境差異で hasRole が未定義と判定されるケースを回避）
        $hasAdminRole = false;
        if ($currentUser) {
            $hasAdminRole = User::whereKey($currentUser->id)
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'admin');
                })
                ->exists();
        }
        if (!$hasAdminRole) {
            abort(403, 'Unauthorized');
        }

        $user = User::findOrFail($id);

        // テナント境界チェック: 同一テナントのみ削除可能
        if ($user->tenant_id !== $currentUser->tenant_id) {
            abort(403, 'Cross-tenant deletion is not allowed');
        }

        // 防御的対策: 外部キー制約（attachments.uploaded_by -> users.id）で
        // 削除が失敗しないよう、参照をNULLへ更新（先にコミット）
        try {
            Attachment::where('uploaded_by', $user->id)->update(['uploaded_by' => null]);
        } catch (\Throwable $e) {
            Log::warning('Failed to nullify attachments.uploaded_by before user deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // ユーザーを削除
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
