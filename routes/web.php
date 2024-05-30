<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\InitializeTenancyMiddleware;

Route::get('/', function () {
    return view('welcome');
});

// 認証関連のルートはテナント識別の対象外にします
require __DIR__.'/auth.php';

Route::middleware([InitializeTenancyMiddleware::class, 'auth'])->group(function () {
    Route::get('/index', [PostController::class, 'index'])
        ->name('index');

    Route::post('/posts', [PostController::class, 'store'])
        ->name('posts.store');

    Route::delete('/posts/{post}', [PostController::class, 'destroy'])
        ->name('posts.destroy');

    Route::post('/posts/search', [PostController::class, 'search'])
        ->name('posts.search');

    Route::resource('/comment', CommentController::class);

    // users
    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->name('users.show');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // check-tenant ルートの追加
    Route::get('/check-tenant', function () {
        return view('check-tenant', ['tenant' => tenant()]);
    })->name('check-tenant');
});
