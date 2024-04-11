<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function saveWithUser(array $data, $userId)
    {
        $this->fill($data); //ユーザー入力から受け取ったデータをモデルに設定（$fillableで指定されているフィールドのみ）
        $this->user_id = $userId; //ユーザーidを定義
        $this->save(); //DBに保存

        return $this; //保存したPostインスタンスを返す
    }
}
