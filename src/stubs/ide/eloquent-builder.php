<?php

/**
 * IDE stub: Illuminate\Database\Eloquent\Builder
 * 静的解析専用。実装は vendor/laravel/framework にあります。
 *
 * @see \Illuminate\Database\Eloquent\Builder
 */
namespace Illuminate\Database\Eloquent;

class Builder
{
    public function whereHas(string $relation, \Closure|string|null $callback = null, string $operator = '>=', int $count = 1) { return $this; }
    public function where($column, $operator = null, $value = null, string $boolean = 'and') { return $this; }
    public function first($columns = ['*']) { return null; }
}
