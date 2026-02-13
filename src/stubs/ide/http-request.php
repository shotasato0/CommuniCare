<?php

/**
 * IDE stub: Illuminate\Http\Request
 * 静的解析専用。実装は vendor/laravel/framework にあります。
 *
 * @see \Illuminate\Http\Request
 */
namespace Illuminate\Http;

class Request
{
    /**
     * @param  string|null  $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable|\App\Models\User|null
     */
    public function user($guard = null) { return null; }

    public function hasSession(): bool { return false; }

    /**
     * @return \Illuminate\Session\Store|null
     */
    public function session() { return null; }
}
