<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\AttachmentController;

// トップページ（Closureを廃止しControllerへ）
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// 認証が必要なルート
Route::middleware(['auth'])->group(function () {
        // ダッシュボード
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // プロフィール
    Route::post('/profile/update-icon', [ProfileController::class, 'updateIcon'])->name('profile.updateIcon');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ユーザーの所属ユニットのforum_idを取得（エンドポイント）
    Route::get('/user-forum-id', [UserController::class, 'getUserForumId']);

    // NOTE: フォーラム/投稿/コメント/添付はテナントドメイン配下に移動（routes/tenant.php）
    // web.php 側では定義しないことで、保存と表示のFSコンテキスト不一致を回避

    // ユーザー（職員）
    Route::resource('users', UserController::class);
    Route::get('/users/{user}/edit-profile', [UserController::class, 'editProfile'])->name('users.editProfile');
    Route::get('/users/{user}/edit-icon', [UserController::class, 'editIcon'])->name('users.editIcon');
    Route::post('/users/{user}/update-icon', [UserController::class, 'updateIcon'])->name('users.updateIcon');

    // ユニット
    Route::resource('units', UnitController::class);

    // 利用者
    Route::resource('residents', ResidentController::class);

    // 管理者権限の譲渡
    Route::post('/admin/transfer-admin', [AdminUserController::class, 'transferAdmin'])->name('admin.transferAdmin');

    // 部署の並び順を保存
    Route::post('/units/sort', [UnitController::class, 'sort'])->name('units.sort');
});

// 法的情報
Route::get('/legal/privacy-policy', [LegalController::class, 'privacyPolicy'])->name('legal.privacyPolicy');
Route::get('/legal/terms-of-service', [LegalController::class, 'termsOfService'])->name('legal.termsOfService');

require __DIR__.'/auth.php';
