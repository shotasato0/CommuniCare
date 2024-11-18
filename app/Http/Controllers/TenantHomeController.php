<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Application;
use Inertia\Inertia;

class TenantHomeController extends Controller
{
    public function index()
    {
        // セッションからドメイン名を取得
        $domain = Session::get('tenant_domain', '不明なドメイン');

        return Inertia::render('TenantHome', [
            'canLogin' => \Route::has('login'),
            'canRegister' => \Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }
}

