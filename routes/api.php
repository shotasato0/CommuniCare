<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ResidentController;

Route::middleware('auth:api')->get('/units', [UnitController::class, 'index'])
    ->name('units.index');

Route::middleware('auth:api')->get('/residents', [ResidentController::class, 'index'])
    ->name('residents.index');

