<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ResidentController;

// 投稿を保存する
Route::post('/posts', [PostController::class, 'store']);

// 投稿を検索する
Route::post('/posts/search', [PostController::class, 'search']);

// 投稿を削除する
Route::delete('/posts/{post}', [PostController::class, 'destroy']);

// コメントを投稿する
Route::post('/comments', [CommentController::class, 'store']);

Route::middleware('auth:api')->get('/units', [UnitController::class, 'index'])
    ->name('units.index');

Route::middleware('auth:api')->get('/residents', [ResidentController::class, 'index'])
    ->name('residents.index');

