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
        $units = Unit::select('id', 'name')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get();
        $forums = Forum::where('tenant_id', auth()->user()->tenant_id)->get();
        
        return Inertia::render("Unit/Register", [
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
            "name" => "required|string|max:255|unique:units,name,NULL,id,tenant_id," . auth()->user()->tenant_id,
        ], [
            "name.required" => "部署名は必須です。",
            "name.string" => "部署名は文字列で入力してください。",
            "name.max" => "部署名は255文字以内で入力してください。",
            "name.unique" => "この部署名は既に登録されています。",
        ]);

        $unit = Unit::create([
            'name' => $request->name,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        $forum = Forum::create([
            'name' => $request->name,
            'unit_id' => $unit->id,
            'description' => $request->description ?? '',
            'visibility' => $request->visibility ?? 'public',
            'status' => 'active',
            'tenant_id' => auth()->user()->tenant_id,
        ]);
        return redirect()->route("dashboard")->with(["success" => "部署登録が完了しました。"]);
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

    /**
     * 部署の並び順を保存
     */
    public function sort(Request $request)
    {
        $units = $request->input('units');
        foreach ($units as $index => $unit) {
            Unit::where('id', $unit['id'])->update(['sort_order' => $index]);
        }
        return response()->json(['message' => '部署の並び順が保存されました']);
    }
}
