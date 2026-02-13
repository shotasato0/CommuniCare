<?php

/**
 * IDE stub for Illuminate\Database\Eloquent\Builder
 *
 * This file is for static analysis only. The actual implementation
 * is provided by the Laravel framework in vendor/.
 *
 * @see \Illuminate\Database\Eloquent\Builder
 */
namespace Illuminate\Database\Eloquent;

/**
 * @template TModel of Model
 * @extends \Illuminate\Database\Query\Builder
 */
class Builder
{
    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param  string  $relation
     * @param  \Closure|string|null  $callback
     * @param  string  $operator
     * @param  int  $count
     * @return static
     */
    public function whereHas(string $relation, \Closure|string|null $callback = null, string $operator = '>=', int $count = 1)
    {
        return $this;
    }
}
