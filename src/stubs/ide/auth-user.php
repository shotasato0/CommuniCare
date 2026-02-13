<?php

/**
 * IDE stub: Illuminate\Foundation\Auth\User (Authenticatable)
 * 静的解析専用。実装は vendor/laravel/framework にあります。
 *
 * @see \Illuminate\Foundation\Auth\User
 */
namespace Illuminate\Foundation\Auth;

use Illuminate\Database\Eloquent\Model;

abstract class User extends Model
{
    public function toArray(): array { return parent::toArray(); }
}
