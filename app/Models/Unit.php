<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'tenant_id'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function forum()
    {
        return $this->hasOne(Forum::class);
    }
}

