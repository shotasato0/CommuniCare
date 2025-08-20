<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\AttachmentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/{type}/{id}/liked-users', [LikeController::class, 'getLikedUsers']);

// Attachment API routes (認証必須)
Route::middleware('auth')->group(function () {
    Route::post('/attachments', [AttachmentController::class, 'store']); // ファイルアップロード
    Route::get('/attachments/{id}', [AttachmentController::class, 'show']); // 添付ファイル詳細
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']); // ファイル削除
    Route::get('/attachments', [AttachmentController::class, 'index']); // ファイル一覧
});