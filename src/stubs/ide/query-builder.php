<?php

/**
 * IDE stub: Illuminate\Database\Query\Builder
 * 静的解析専用。実装は vendor/laravel/framework にあります。
 *
 * @see \Illuminate\Database\Query\Builder
 */
namespace Illuminate\Database\Query;

class Builder
{
    /**
     * @param  array|string  $columns
     * @return static
     */
    public function select($columns = ['*']) { return $this; }

    /**
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return static
     */
    public function where($column, $operator = null, $value = null, string $boolean = 'and') { return $this; }

    /**
     * @param  array|string  $columns
     * @return object|null
     */
    public function first($columns = ['*']) { return null; }
}
