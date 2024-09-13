<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PostController;

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

Route::get('/home', function () {
    return Inertia::render('TenantHome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('tenant-home');

// テナント識別を行うルート
Route::middleware([App\Http\Middleware\InitializeTenancyCustom::class])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('/forum', [PostController::class, 'index'])->name('forum.index');
    Route::post('/forum/post', [PostController::class, 'store'])->name('forum.store');
    Route::delete('/forum/post/{id}', [PostController::class, 'destroy'])->name('forum.destroy');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
