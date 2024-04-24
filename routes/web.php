<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/{any}', function () {
    return view('layouts.app');
})->where('any', '.*');

Route::get('/index', [PostController::class, 'index'])
    ->name('index');

Route::post('/posts', [PostController::class, 'store'])
    ->name('posts.store');

Route::delete('/posts/{post}', [PostController::class, 'destroy'])
    ->name('posts.destroy');

Route::post('/posts/search', [PostController::class, 'search'])
    ->name('posts.search');

Route::resource('/comment', CommentController::class);

//users
Route::get('/users', [UserController::class, 'index'])
    ->name('users.index');

Route::get('/users/{user}', [UserController::class, 'show'])
    ->name('users.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
