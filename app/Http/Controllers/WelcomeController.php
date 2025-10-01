<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index()
    {
        $env = config('app.env');
        $domain = match ($env) {
            'local'      => config('guest.domains.local'),
            'staging'    => config('guest.domains.staging'),
            'production' => config('guest.domains.production'),
            default      => config('guest.domains.production'),
        };
        $scheme = config('guest.protocol', $env === 'local' ? 'http' : 'https');
        $port   = match ($env) {
            'local'      => config('guest.ports.local'),
            'staging'    => config('guest.ports.staging'),
            'production' => config('guest.ports.production'),
            default      => null,
        };
        $host = $domain . ($port ? ":{$port}" : '');
        $guestUrl = "{$scheme}://{$host}/";

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            // 中央→テナント遷移URLは絶対URLで返し、フロントは通常リンクでフル遷移
            'guestLoginUrl' => $guestUrl,
        ]);
    }
}
