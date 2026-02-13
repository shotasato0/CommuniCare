<?php

/**
 * IDE stub for Illuminate\Http\Request
 *
 * This file is for static analysis only. The actual implementation
 * is provided by the Laravel framework in vendor/.
 *
 * @see \Illuminate\Http\Request
 */
namespace Illuminate\Http;

class Request
{
    /**
     * Get the user making the request.
     *
     * @param  string|null  $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user($guard = null)
    {
        return null;
    }

    /**
     * Determine if the request has a valid session.
     *
     * @return bool
     */
    public function hasSession(): bool
    {
        return false;
    }

    /**
     * Get the session associated with the request.
     *
     * @return \Illuminate\Session\Store
     */
    public function session()
    {
    }
}
