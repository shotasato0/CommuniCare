<?php

/**
 * IDE stub: Illuminate\Database\Eloquent\Model
 * 静的解析専用。実装は vendor/laravel/framework にあります。
 *
 * @see \Illuminate\Database\Eloquent\Model
 * @property int $id
 */
namespace Illuminate\Database\Eloquent;

abstract class Model
{
    public function toArray(): array { return []; }
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null) {}
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null) {}
    public function morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null) {}
    public function morphOne(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null) {}
}
