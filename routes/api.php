<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ResidentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('units', UnitController::class);

Route::apiResource('posts', PostController::class);

Route::middleware('auth:sanctum')->get('/posts', [PostController::class, 'index']);

Route::get('/units/{unit}/posts', [PostController::class, 'forUnit']);

Route::apiResource('residents', ResidentController::class);

