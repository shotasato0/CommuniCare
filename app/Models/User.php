<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username_id',
        'password',
        'tenant_id',
        'tel',          // 電話番号フィールドの追加
        'email',        // メールアドレスフィールドの追加
        'icon',         // プロフィール写真用のURLフィールド
        'unit_id',      // 担当入居者の関連ID
        'guest_session_id', // ゲストユーザーのセッションID
    ];
    

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function forum()
    {
        return $this->unit->forum(); //単一のフォーラムを返す
    }

    public function likes()
    {
        return $this->hasMany(Like::class); // 多対多のリレーション
    }

    /**
     * Get all attachments for the user (アイコン画像等)
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the user's icon attachment
     */
    public function iconAttachment()
    {
        return $this->morphOne(Attachment::class, 'attachable')->where('file_type', 'image');
    }

    /**
     * Get the user's icon URL attribute - 完全に統一システムで管理
     */
    public function getIconUrlAttribute(): string 
    {
        $attachment = $this->iconAttachment;
        return $attachment 
            ? route('attachments.show', ['attachment' => $attachment->id])
            : '/images/default_user_icon.png';
    }
}
