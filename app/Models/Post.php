<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'title', 'message', 'forum_id', 'quoted_post_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable'); // ポリモーフィックリレーション
    }

    public function forum()
    {
        return $this->belongsTo(Forum::class);
    }

    // 引用元の投稿へのリレーション
    public function quotedPost()
    {
        return $this->belongsTo(Post::class, 'quoted_post_id')->withTrashed();
    }
}

