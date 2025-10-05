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
        if (is_string($domain) && $domain !== '') {
            $host = $domain . ($port ? ":{$port}" : '');
        } else {
            // フォールバック: app.url からスキーム/ホスト/ポートを解決
            $appUrl = (string) config('app.url', '');
            $fallbackHost   = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
            $fallbackPort   = parse_url($appUrl, PHP_URL_PORT);
            $fallbackScheme = parse_url($appUrl, PHP_URL_SCHEME) ?: ($env === 'local' ? 'http' : 'https');
            $host   = $fallbackHost . ($fallbackPort ? ":{$fallbackPort}" : '');
            $scheme = $fallbackScheme ?: $scheme;
        }

        $guestUrl = "{$scheme}://{$host}/";

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            // 中央→テナント遷移URLは絶対URLで返し、フロントは通常リンクでフル遷移
            'guestLoginUrl' => $guestUrl,
        ]);
    }
}
