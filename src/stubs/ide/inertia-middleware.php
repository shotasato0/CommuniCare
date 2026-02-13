<?php

/**
 * IDE stub: Inertia\Middleware
 * 静的解析専用。実装は vendor/inertiajs/inertia-laravel にあります。
 *
 * @see \Inertia\Middleware
 */
namespace Inertia;

use Closure;
use Illuminate\Http\Request;

abstract class Middleware
{
    public function handle(Request $request, Closure $next) {}
    public function version(Request $request) {}
    public function share(Request $request): array { return []; }
    public function rootView(Request $request): string { return 'app'; }
}
