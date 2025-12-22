<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantHomeController;
use App\Http\Controllers\Auth\GuestLoginController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\CalendarController;

// テナントドメイン限定のルートは、bootstrap/app.php 側の Route::domain() で
// ミドルウェア（InitializeTenancyByDomain 等）を付与して読み込まれる前提。

// テナントのトップページ（ゲストデモ入口もここに統一）
Route::get('/', [TenantHomeController::class, 'index'])->name('guest.login.view');

// 後方互換: 旧URLからルートへ恒久的リダイレクト（周知期間後に削除予定）
Route::redirect('/guest/login', '/', 301);

// 実行：ゲストで入る（中央には置かない）
Route::middleware('guest')->get('/guest/user/login', [GuestLoginController::class, 'loginAsGuest'])->name('guest.user.login');

// 認証配下のテナントルート（保存・表示のFSコンテキストを統一）
Route::middleware(['auth'])->group(function () {
    // 添付
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // フォーラム
    Route::get('/forum', [ForumController::class, 'index'])->name('forum.index');

    // 投稿
    Route::post('/forum/post', [PostController::class, 'store'])->name('forum.store');
    Route::delete('/forum/post/{id}', [PostController::class, 'destroy'])->name('forum.destroy');
    Route::post('/forum/post/{post}/attachments', [PostController::class, 'addAttachments'])->name('forum.post.attachments.add');
    Route::delete('/forum/post/{post}/attachments/{attachmentId}', [PostController::class, 'removeAttachment'])->name('forum.post.attachments.remove');

    // いいね
    Route::post('/like/toggle', [LikeController::class, 'toggleLike'])->name('like.toggle');

    // コメント
    Route::post('/forum/comment', [CommentController::class, 'store'])->name('comment.store');
    Route::delete('/forum/comment/{id}', [CommentController::class, 'destroy'])->name('comment.destroy');
    Route::post('/forum/comment/{comment}/attachments', [CommentController::class, 'addAttachments'])->name('forum.comment.attachments.add');
    Route::delete('/forum/comment/{comment}/attachments/{attachmentId}', [CommentController::class, 'removeAttachment'])->name('forum.comment.attachments.remove');

    // カレンダー
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/week', [CalendarController::class, 'week'])->name('calendar.week');
    Route::get('/calendar/day/{date?}', [CalendarController::class, 'day'])->name('calendar.day');

    // スケジュール
    Route::get('/calendar/schedules', [ScheduleController::class, 'index'])->name('calendar.schedules.index');
    Route::post('/calendar/schedule', [ScheduleController::class, 'store'])->name('calendar.schedule.store');
    Route::put('/calendar/schedule/{schedule}', [ScheduleController::class, 'update'])->name('calendar.schedule.update');
    Route::delete('/calendar/schedule/{schedule}', [ScheduleController::class, 'destroy'])->name('calendar.schedule.destroy');
});
