<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'tenant_id', 'sort_order'];

    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function forum()
    {
        return $this->hasOne(Forum::class);
    }
}

