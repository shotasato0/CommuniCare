<?php

/**
 * IDE stub: Illuminate\Support\Facades\DB
 * 静的解析専用。実装は vendor/laravel/framework にあります。
 *
 * @see \Illuminate\Support\Facades\DB
 */
namespace Illuminate\Support\Facades;

use Illuminate\Database\Query\Builder;

class DB
{
    public static function connection($connection = null) {}

    /**
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $table
     * @param  string|null  $as
     * @return \Illuminate\Database\Query\Builder
     */
    public static function table($table, $as = null): Builder
    {
        return new Builder();
    }
}
