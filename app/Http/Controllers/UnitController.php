<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\Forum;
use Inertia\Inertia;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function listForSidebar()
    {
        $units = Unit::with('forum')->get();
        return Inertia::render('Forum', [
            'units' => $units,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::select('id', 'name')->get();
        $forums = Forum::all();
        return inertia("Unit/Register", [
            'units' => $units,
            'forums' => $forums,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:255|unique:units,name",
        ], [
            "name.required" => "部署名は必須です。",
            "name.string" => "部署名は文字列で入力してください。",
            "name.max" => "部署名は255文字以内で入力してください。",
            "name.unique" => "この部署名は既に登録されています。",
        ]);

        $unit = Unit::create($request->all());

        $forum = Forum::create([
            'name' => $request->name,
            'unit_id' => $unit->id,
            'description' => 'この掲示板は' . $request->name . 'の掲示板です。',
            'visibility' => 'public',
            'status' => 'active',
        ]);
        return redirect()->route("dashboard")->with(["success" => "ユニット登録が完了しました。"]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Unit::find($id)->delete();
        return redirect()->route("units.create")->with(["success" => "部署の削除が完了しました"]);
    }
}
