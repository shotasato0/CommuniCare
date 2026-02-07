<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\Unit\UnitStoreRequest;
use App\Http\Requests\Unit\UnitSortRequest;
use App\Services\UnitService;

class UnitController extends Controller
{
    protected $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    /**
     * Display a listing of the resource.
     */
    public function listForSidebar()
    {
        $units = $this->unitService->getUnitsWithForum();
        
        return Inertia::render('Forum', [
            'units' => $units,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = $this->unitService->getUnitsForManagement();
        $forums = $this->unitService->getForumsForTenant();
        
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
        $this->unitService->createUnit($request);
        
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
    public function destroy(int $id)
    {
        $this->unitService->deleteUnit($id);
        
        return redirect()->route("units.create")->with(["success" => "部署の削除が完了しました"]);
    }

    /**
     * 部署の並び順を保存
     */
    public function sort(UnitSortRequest $request)
    {
        $this->unitService->updateSortOrder($request);
        
        return redirect()->route('forum.index');
    }
}
