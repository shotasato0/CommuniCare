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
        //
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
         ]);
     }





    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $units = Unit::all();
        return Inertia::render('Users/Edit', compact('user', 'units'));
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

    return redirect()->route('users.edit', $user->id)
            ->with('success', 'ユーザー情報が更新されました。');
    }

    public function editIcon(User $user)
{
    return Inertia::render('Users/IconEdit', [
        'user' => $user,
    ]);
}

public function updateIcon(Request $request)
{
    // バリデーション
    $request->validate([
        'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    // ファイルを取得
    $file = $request->file('icon');
    
    // テナントDB名を取得
    $tenantDbName = $request->user()->tenant->tenancy_db_name;

    // 一意のファイル名を生成
    $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
    
    // ファイルを保存
    $path = $file->storeAs('public/' . $tenantDbName . '/icons', $fileName);

    // 既存のアイコンを削除（必要に応じて）
    if ($request->user()->icon) {
        Storage::delete('public/' . $request->user()->icon);
    }

    // データベースに新しいパスを保存
    $user = $request->user();
    $user->icon = $tenantDbName . '/icons/' . $fileName;
    $user->save();

    // 成功したらレスポンスを返す
    return redirect()->back()->with('success', 'プロフィール画像が更新されました。');
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
