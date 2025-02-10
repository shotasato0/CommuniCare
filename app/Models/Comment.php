<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['tenant_id', 'user_id', 'post_id', 'parent_id', 'message', 'img', 'forum_id'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function children()
    {
        // 再帰的にすべての階層の子コメントを取得
        return $this->hasMany(Comment::class, 'parent_id')->with('children.user');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable'); // ポリモーフィックリレーション
    }

    public function forum()
    {
        return $this->belongsTo(Forum::class);
    }

    public function getFormattedMessageAttribute()
    {
        return nl2br(preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            e($this->message)
        ));
    }
}

