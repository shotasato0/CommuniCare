<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\Request;
use App\Models\Unit;
use Inertia\Inertia;

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

    return Inertia::render('Residents/Index', [
            'residents' => $residents,
            'units' => Unit::all(),
            'selectedUnitId' => $unitId
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
    public function show(Resident $resident)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resident $resident)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Resident $resident)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resident $resident)
    {
        //
    }
}
