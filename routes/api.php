<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\Api\UserController;

Route::get('/user', [UserController::class, 'me'])->middleware('auth:sanctum');

Route::get('/{type}/{id}/liked-users', [LikeController::class, 'getLikedUsers']);
Route::get('/{type}/{id}/liked-users', [LikeController::class, 'getLikedUsers']);
