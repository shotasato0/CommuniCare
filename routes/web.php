<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\TenantHomeController;

// テナント識別をスキップするルート
Route::middleware([])->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('welcome');
});

// テナント識別を行うルート
Route::middleware([App\Http\Middleware\InitializeTenancyCustom::class])->group(function () {
    Route::get('/home', [TenantHomeController::class, 'index'])
    ->name('tenant-home');

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::post('/profile/update-icon', [ProfileController::class, 'updateIcon'])->name('profile.updateIcon');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // ユーザーの所属ユニットのforum_idを取得（エンドポイント）
        Route::get('/user-forum-id', [UserController::class, 'getUserForumId']);

        // フォーラム
        Route::get('/forum', [ForumController::class, 'index'])->name('forum.index');

        // 投稿
        Route::post('/forum/post', [PostController::class, 'store'])->name('forum.store');
        Route::delete('/forum/post/{id}', [PostController::class, 'destroy'])->name('forum.destroy');
        Route::post('/like/toggle', [LikeController::class, 'toggleLike'])->name('like.toggle');

        // コメント
        Route::post('/forum/comment', [CommentController::class, 'store'])->name('comment.store');
        Route::delete('/forum/comment/{id}', [CommentController::class, 'destroy'])->name('comment.destroy');

        Route::resource('users', UserController::class);
        Route::get('/users/{user}/edit-profile', [UserController::class, 'editProfile'])->name('users.editProfile');
        Route::get('/users/{user}/edit-icon', [UserController::class, 'editIcon'])->name('users.editIcon');
        Route::post('/users/{user}/update-icon', [UserController::class, 'updateIcon'])->name('users.updateIcon');

        // Route::middleware(['role:admin'])->group(function () {
        //     Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
        // });
        // Route::get('/units/list-for-sidebar', [UnitController::class, 'listForSidebar'])->name('units.listForSidebar');
        Route::resource('units', UnitController::class);

        // テナントの管理者を移動
        Route::get('/admin/transfer-admin', [UserController::class, 'showTransferAdminForm'])->name('admin.transfer.view');
        Route::post('/admin/transfer-admin', [UserController::class, 'transferAdmin'])->name('admin.transferAdmin');
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

require __DIR__.'/auth.php';
