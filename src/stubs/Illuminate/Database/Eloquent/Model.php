<?php

/**
 * IDE stub for Illuminate\Database\Eloquent\Model
 *
 * This file is for static analysis only. The actual implementation
 * is provided by the Laravel framework in vendor/.
 *
 * @see \Illuminate\Database\Eloquent\Model
 * @property int $id
 */
namespace Illuminate\Database\Eloquent;

abstract class Model
{
    /**
     * Convert the model instance to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [];
    }

    /**
     * Define a one-to-one inverse relationship.
     *
     * @template TRelated of \Illuminate\Database\Eloquent\Model
     * @param  class-string<TRelated>  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $ownerKey
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<TRelated, $this>
     */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null)
    {
    }

    /**
     * Define a one-to-many relationship.
     *
     * @template TRelated of \Illuminate\Database\Eloquent\Model
     * @param  class-string<TRelated>  $related
     * @param  string|null  $foreignKey
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<TRelated, $this>
     */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @template TRelated of \Illuminate\Database\Eloquent\Model
     * @param  class-string<TRelated>  $related
     * @param  string  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<TRelated, $this>
     */
    public function morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null)
    {
    }

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @template TRelated of \Illuminate\Database\Eloquent\Model
     * @param  class-string<TRelated>  $related
     * @param  string  $name
     * @param  string|null  $type
     * @param  string|null  $id
     * @param  string|null  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<TRelated, $this>
     */
    public function morphOne(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null)
    {
    }
}
