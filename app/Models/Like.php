<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'user_id', 'likeable_id', 'likeable_type'];

    // いいねしたモデルを取得
    public function likeable()
    {
        return $this->morphTo(); // ポリモーフィックリレーション
    }

    // いいねしたユーザーを取得
    public function user()
    {
        return $this->belongsTo(User::class); // 多対一のリレーション
    }
}

