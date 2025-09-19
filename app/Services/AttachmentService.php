<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Exceptions\Custom\TenantViolationException;
use App\Traits\SecurityValidationTrait;
use App\Traits\TenantBoundaryCheckTrait;

class AttachmentService
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;

    // サポートするファイル形式
    private const SUPPORTED_TYPES = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'],
        'pdf' => ['pdf'],
        'document' => ['doc', 'docx'],
        'excel' => ['xls', 'xlsx'],
        'text' => ['txt', 'csv', 'rtf']
    ];

    // MIME タイプマッピング
    private const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'rtf' => 'application/rtf'
    ];

    // ファイルサイズ制限（バイト）
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * サポートされるファイルタイプを取得（マイグレーション用）
     */
    public static function getSupportedFileTypes(): array
    {
        return array_keys(self::SUPPORTED_TYPES);
    }

    /**
     * 複数ファイルを安全にアップロード
     */
    public function uploadFiles(array $files, string $attachableType, int $attachableId): array
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        // マルチテナント境界チェック
        $this->validateTenantBoundary($attachableType, $attachableId, $currentUser->tenant_id);
        
        $uploadedAttachments = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $attachment = $this->uploadSingleFile($file, $attachableType, $attachableId);
                if ($attachment) {
                    $uploadedAttachments[] = $attachment;
                }
            }
        }
        
        // セキュリティログ記録
        $this->auditAction('files_uploaded', [
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'files_count' => count($uploadedAttachments),
            'total_size' => array_sum(array_column($uploadedAttachments, 'file_size'))
        ]);
        
        return $uploadedAttachments;
    }

    /**
     * 単一ファイルの安全なアップロード
     */
    public function uploadSingleFile(UploadedFile $file, string $attachableType, int $attachableId): ?Attachment
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        // マルチテナント境界チェック
        $this->validateTenantBoundary($attachableType, $attachableId, $currentUser->tenant_id);
        
        // ファイル検証
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException($validation['error']);
        }
        
        // ファイル情報取得
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $fileType = $this->getFileType($extension);
        $mimeType = $file->getMimeType() ?? $this->getMimeType($extension);
        
        // 安全なファイル名生成
        $fileName = $this->generateSafeFileName($originalName, $extension);
        $filePath = $this->getStoragePath($fileType) . '/' . $fileName;
        
        // ファイルハッシュ生成（重複検出用）
        $hash = hash_file('sha256', $file->getRealPath());
        
        // 重複チェック（同一テナント内）
        $existingAttachment = $this->findDuplicateFile($hash, $currentUser->tenant_id);
        if ($existingAttachment) {
            // 既存の物理ファイルが現在のテナントFSに存在するかを検証
            $existingPath = $existingAttachment->file_path;
            $existsOnTenantFs = Storage::disk('public')->exists($existingPath);
            if ($existsOnTenantFs) {
                // 物理実体が存在する → DBだけ複製して容量節約
                return $this->duplicateAttachment($existingAttachment, $attachableType, $attachableId);
            }
            // 物理実体が存在しない → 過去の保存が中央FS/他テナントにあり欠損の可能性
            // 安全のため今回のアップロードを新規保存として扱う（self-healは別途コマンドで実施）
            Log::warning('AttachmentService: Duplicate hash found but file missing on tenant FS, saving new copy', [
                'existing_attachment_id' => $existingAttachment->id,
                'existing_path' => $existingPath,
                'tenant_id' => $currentUser->tenant_id,
                'attachable_type' => $attachableType,
                'attachable_id' => $attachableId,
            ]);
            // 続行して新規保存
        }
        
        // Laravel Storage使用（推奨方法）
        Log::info('AttachmentService: Attempting to save file', [
            'filePath' => $filePath,
            'originalName' => $originalName,
            'fileSize' => $file->getSize()
        ]);
        
        try {
            // Laravel Storageを使用してファイル保存
            $storedPath = $file->storeAs(
                dirname($filePath), // ディレクトリパス（例: attachments/images）
                basename($filePath), // ファイル名
                'public' // ディスク
            );
            
            if (!$storedPath) {
                throw new \RuntimeException('ファイルの保存に失敗しました（storeAs returned false）');
            }
            
            // 保存確認
            $actualFilePath = $storedPath;
            $exists = Storage::disk('public')->exists($actualFilePath);
            $size = $exists ? Storage::disk('public')->size($actualFilePath) : 0;
            
            Log::info('AttachmentService: File saved successfully', [
                'actualFilePath' => $actualFilePath,
                'file_exists' => $exists,
                'file_size' => $size
            ]);
            
            if (!$exists) {
                throw new \RuntimeException('ファイル保存確認に失敗しました');
            }
            
        } catch (\Exception $e) {
            Log::error('AttachmentService: File save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('ファイルの保存に失敗しました: ' . $e->getMessage());
        }
        
        // Attachmentレコード作成
        $attachment = Attachment::create([
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'original_name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $actualFilePath, // 実際の保存パスを使用
            'file_size' => $file->getSize(),
            'mime_type' => $mimeType,
            'file_type' => $fileType,
            'tenant_id' => $currentUser->tenant_id,
            'uploaded_by' => $currentUser->id,
            'hash' => $hash,
            'is_safe' => $this->performSecurityScan($file)
        ]);
        
        return $attachment;
    }

    /**
     * ファイル削除（セキュリティチェック付き）
     */
    public function deleteAttachment(\App\Models\Attachment|int $attachment): bool
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (!$attachment instanceof Attachment) {
            // テナントスコープを外して取得し、境界チェックを自前で行う
            $attachment = Attachment::withoutGlobalScopes()->find($attachment);
        }
        if (!$attachment) {
            return false;
        }
        
        // テナント境界チェック
        if ($attachment->tenant_id !== $currentUser->tenant_id) {
            throw new TenantViolationException(
                currentTenantId: $currentUser->tenant_id,
                resourceTenantId: $attachment->tenant_id,
                resourceType: 'attachment',
                resourceId: $attachment->id
            );
        }
        
        // 権限チェック（アップロード者または管理者のみ削除可能）
        if ($attachment->uploaded_by !== $currentUser->id && !$currentUser->hasRole('admin')) {
            return false;
        }
        
        // 物理ファイル削除（Storage::disk('public') を使用）
        Storage::disk('public')->delete($attachment->file_path);
        
        // レコード削除
        $deleted = $attachment->delete();
        
        // セキュリティログ記録
        if ($deleted) {
            $this->auditAction('attachment_deleted', [
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->original_name,
                'file_size' => $attachment->file_size
            ]);
        }
        
        return $deleted;
    }

    /**
     * ファイル検証
     */
    private function validateFile(UploadedFile $file): array
    {
        // ファイルサイズチェック
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return [
                'valid' => false,
                'error' => 'ファイルサイズが制限を超えています（最大10MB）'
            ];
        }
        
        // 拡張子チェック
        $extension = strtolower($file->getClientOriginalExtension());
        if (!$this->isSupportedExtension($extension)) {
            return [
                'valid' => false,
                'error' => 'サポートされていないファイル形式です'
            ];
        }
        
        // MIMEタイプチェック
        $expectedMime = $this->getMimeType($extension);
        $actualMime = $file->getMimeType();
        if ($actualMime && $actualMime !== $expectedMime) {
            return [
                'valid' => false,
                'error' => 'ファイル形式が一致しません'
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * テナント境界チェック
     */
    private function validateTenantBoundary(string $attachableType, int $attachableId, string $tenantId): void
    {
        // 関連モデルのテナント境界チェック
        if ($attachableType === 'App\Models\Post') {
            $post = \App\Models\Post::find($attachableId);
            if ($post && $post->tenant_id !== $tenantId) {
                throw new TenantViolationException(
                    currentTenantId: $tenantId,
                    resourceTenantId: $post->tenant_id,
                    resourceType: 'post',
                    resourceId: $attachableId
                );
            }
        }
        // 他のモデルタイプも必要に応じて追加
    }

    /**
     * サポートされている拡張子かチェック
     */
    private function isSupportedExtension(string $extension): bool
    {
        foreach (self::SUPPORTED_TYPES as $extensions) {
            if (in_array($extension, $extensions)) {
                return true;
            }
        }
        return false;
    }

    /**
     * ファイルタイプ取得
     */
    private function getFileType(string $extension): string
    {
        foreach (self::SUPPORTED_TYPES as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        return 'document'; // デフォルト
    }

    /**
     * MIMEタイプ取得
     */
    private function getMimeType(string $extension): string
    {
        return self::MIME_TYPES[$extension] ?? 'application/octet-stream';
    }

    /**
     * 安全なファイル名生成
     */
    private function generateSafeFileName(string $originalName, string $extension): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = Str::slug($name) ?: 'file';
        return $safeName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * ストレージパス取得
     */
    private function getStoragePath(string $fileType): string
    {
        return match($fileType) {
            'image' => 'attachments/images',
            'pdf' => 'attachments/pdfs',
            'document' => 'attachments/documents',
            'excel' => 'attachments/excel',
            'text' => 'attachments/text',
            default => 'attachments/misc'
        };
    }

    /**
     * 重複ファイル検索
     */
    private function findDuplicateFile(string $hash, string $tenantId): ?Attachment
    {
        return Attachment::where('hash', $hash)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * 重複ファイルのAttachment作成
     */
    private function duplicateAttachment(Attachment $source, string $attachableType, int $attachableId): Attachment
    {
        return Attachment::create([
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'original_name' => $source->original_name,
            'file_name' => $source->file_name,
            'file_path' => $source->file_path,
            'file_size' => $source->file_size,
            'mime_type' => $source->mime_type,
            'file_type' => $source->file_type,
            'tenant_id' => $source->tenant_id,
            'uploaded_by' => Auth::id(),
            'hash' => $source->hash,
            'is_safe' => $source->is_safe
        ]);
    }

    /**
     * セキュリティスキャン実行
     */
    private function performSecurityScan(UploadedFile $file): bool
    {
        // TODO: 将来的にウイルススキャン等を実装
        // 現在は基本的なチェックのみ
        
        // ファイルの先頭バイトチェック（基本的なファイル形式検証）
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle) {
            $header = fread($handle, 16);
            fclose($handle);
            
            // 悪意のあるファイルヘッダーのチェック
            $dangerousPatterns = [
                "\x4D\x5A", // PE executable
                "\x7F\x45\x4C\x46", // ELF executable
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (str_starts_with($header, $pattern)) {
                    return false;
                }
            }
        }
        
        return true;
    }
}
