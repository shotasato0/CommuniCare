<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index()
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            // 中央→テナント遷移URL（サーバ側で生成し、フロントは絶対URLで遷移）
            'guestLoginUrl' => route('guest.login.redirect'),
        ]);
    }
}
