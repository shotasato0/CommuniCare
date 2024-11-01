<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forum extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'unit_id',
        'description',
        'visibility',
        'status',
    ];

    /**
     * Get the unit that owns the forum.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the posts for the forum.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the comments for the forum.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the admin (unit leader) of the forum.
     */
    public function admin()
    {
        return $this->unit->leader;
    }
}
