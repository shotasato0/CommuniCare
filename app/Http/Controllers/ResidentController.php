<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\Request;
use App\Models\Unit;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Requests\Resident\ResidentStoreRequest;
use App\Http\Requests\Resident\ResidentUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class ResidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $unitId = $request->input('unit_id');

        $residents = Resident::when($unitId, function ($query) use ($unitId) {
            return $query->where('unit_id', $unitId);
        })->with('unit')->get();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // 管理者ロールをチェック（Spatie Permissionを使用）
        $isAdmin = $user->roles()->where('name', 'admin')->exists();
        
        return Inertia::render('Residents/Index', [
            'residents' => $residents,
            'units' => Unit::where('tenant_id', $user->tenant_id)->orderBy('sort_order')->get(),
            'selectedUnitId' => $unitId,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Residents/Register', [
            'units' => Unit::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResidentStoreRequest $request)
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => Auth::user()->tenant_id
        ]);

        Resident::create($data);

        return to_route('residents.index')
            ->with('success', '利用者を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Resident $resident)
    {
        return Inertia::render('Residents/Show', [
            'resident' => $resident->load('unit'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resident $resident)
    {
        return Inertia::render('Residents/Edit', [
            'resident' => $resident,
            'units' => Unit::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ResidentUpdateRequest $request, Resident $resident)
    {
        $resident->update($request->validated());

        return to_route('residents.index')
            ->with('success', '利用者情報を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resident $resident)
    {
        $resident->delete();
        return to_route('residents.index')
            ->with('success', '利用者を削除しました。');
    }
}
