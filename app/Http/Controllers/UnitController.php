<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\Forum;
use Inertia\Inertia;
use App\Http\Requests\Unit\UnitStoreRequest;
use App\Http\Requests\Unit\UnitSortRequest;
use Illuminate\Support\Facades\Auth;

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
        $units = Unit::select('id', 'name', 'sort_order', 'created_at')
            ->where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
        $forums = Forum::where('tenant_id', Auth::user()->tenant_id)->get();
        
        return Inertia::render("Unit/Register", [
            'units' => $units,
            'forums' => $forums,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UnitStoreRequest $request)
    {
        // 最新の並び順を取得
        $maxSortOrder = Unit::where('tenant_id', Auth::user()->tenant_id)
            ->max('sort_order') ?? -1;

        $unit = Unit::create([
            'name' => $request->name,
            'tenant_id' => Auth::user()->tenant_id,
            'sort_order' => $maxSortOrder + 1,
        ]);

        // 部署に対応するフォーラムを作成（戻り値は不要）
        Forum::create([
            'name' => $request->name,
            'unit_id' => $unit->id,
            'description' => $request->description ?? '',
            'visibility' => $request->visibility ?? 'public',
            'status' => 'active',
            'tenant_id' => Auth::user()->tenant_id,
        ]);
        
        return redirect()->route("units.create")->with(["success" => "部署登録が完了しました。"]);
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
        $unit = Unit::find($id);
        if ($unit) {
            $unit->delete();
        }
        
        return redirect()->route("units.create")->with(["success" => "部署の削除が完了しました"]);
    }

    /**
     * 部署の並び順を保存
     */
    public function sort(UnitSortRequest $request)
    {
        $units = $request->validated()['units'];
        foreach ($units as $index => $unit) {
            Unit::where('id', $unit['id'])->update(['sort_order' => $index]);
        }
        return redirect()->route('forum.index');
    }
}
