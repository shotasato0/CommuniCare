<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Attachment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'attachable_type',
        'attachable_id', 
        'original_name',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_type',
        'tenant_id',
        'uploaded_by',
        'hash',
        'is_safe'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_safe' => 'boolean',
    ];

    /**
     * ポリモーフィックリレーション（投稿・コメント等）
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * アップロードしたユーザー
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * ファイルサイズを人間が読める形式で取得
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' bytes';
    }

    /**
     * ファイルが画像かどうか判定
     */
    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    /**
     * ファイルがドキュメントかどうか判定
     */
    public function isDocument(): bool
    {
        return in_array($this->file_type, ['pdf', 'document', 'excel', 'text']);
    }

    /**
     * ファイルの拡張子を取得
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * ダウンロード可能かチェック
     */
    public function isDownloadable(): bool
    {
        return $this->is_safe && file_exists(storage_path('app/public/' . $this->file_path));
    }

    /**
     * ファイルアクセス用のURL取得
     */
    public function getUrlAttribute(): string
    {
        return route('attachments.show', $this);
    }

    /**
     * 画像表示用の最適化されたURL（プレビュー用）
     */
    public function getPreviewUrlAttribute(): string
    {
        if ($this->isImage()) {
            return $this->url;
        }
        
        // 非画像の場合はファイルタイプのアイコンを返す
        return asset('images/file-icons/' . $this->file_type . '.svg');
    }
}