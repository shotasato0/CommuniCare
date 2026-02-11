<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\Resident\ResidentStoreRequest;
use App\Http\Requests\Resident\ResidentUpdateRequest;
use App\Services\ResidentService;

class ResidentController extends Controller
{
    protected $residentService;

    public function __construct(ResidentService $residentService)
    {
        $this->residentService = $residentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $unitId = $request->input('unit_id');

        $residents = $this->residentService->getResidents($unitId);
        $units = $this->residentService->getUnitsForTenant();
        $isAdmin = $this->residentService->isAdmin();
        
        return Inertia::render('Residents/Index', [
            'residents' => $residents,
            'units' => $units,
            'selectedUnitId' => $unitId,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = $this->residentService->getUnitsForTenant();
        
        return Inertia::render('Residents/Register', [
            'units' => $units,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResidentStoreRequest $request)
    {
        $this->residentService->createResident($request);

        return to_route('residents.index')
            ->with('success', '利用者を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $resident = $this->residentService->getResident($id);
        
        return Inertia::render('Residents/Show', [
            'resident' => $resident,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $resident = $this->residentService->getResident($id);
        $units = $this->residentService->getUnitsForTenant();
        
        return Inertia::render('Residents/Edit', [
            'resident' => $resident,
            'units' => $units,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ResidentUpdateRequest $request, int $id)
    {
        $this->residentService->updateResident($request, $id);

        return to_route('residents.index')
            ->with('success', '利用者情報を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->residentService->deleteResident($id);
        
        return to_route('residents.index')
            ->with('success', '利用者を削除しました。');
    }
}
