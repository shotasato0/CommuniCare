<?php

/**
 * IDE stub for Inertia\Middleware
 *
 * This file is for static analysis only. The actual implementation
 * is provided by inertiajs/inertia-laravel in vendor/.
 *
 * @see \Inertia\Middleware
 */
namespace Inertia;

use Closure;
use Illuminate\Http\Request;

abstract class Middleware
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
    }

    /**
     * Determine the current asset version.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
    }

    /**
     * Define the props that are shared by default.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [];
    }

    /**
     * Determine the root view that is loaded on the first page visit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    public function rootView(Request $request): string
    {
        return 'app';
    }
}
