<?php

/**
 * IDE stub for Illuminate\Foundation\Auth\User (Authenticatable)
 *
 * This file is for static analysis only. The actual implementation
 * is provided by the Laravel framework in vendor/.
 *
 * @see \Illuminate\Foundation\Auth\User
 */
namespace Illuminate\Foundation\Auth;

use Illuminate\Database\Eloquent\Model;

abstract class User extends Model
{
    /**
     * Convert the model instance to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return parent::toArray();
    }
}
