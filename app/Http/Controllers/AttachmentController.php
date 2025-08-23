<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exceptions\Custom\TenantViolationException;

class AttachmentController extends Controller
{
    private AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * ファイル表示・ダウンロード用ルート
     * セキュアなファイルアクセスを提供
     */
    public function show(Attachment $attachment): StreamedResponse
    {
        // テナント境界チェック
        $this->validateTenantAccess($attachment);
        
        // ファイル存在チェック
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'ファイルが見つかりません');
        }
        
        // ファイルストリーミング応答
        return Storage::disk('public')->response(
            $attachment->file_path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
                'Cache-Control' => 'public, max-age=31536000',
                'Expires' => now()->addYear()->toRfc7231String(),
            ]
        );
    }

    /**
     * ファイル削除
     */
    public function destroy(Attachment $attachment): Response
    {
        // テナント境界チェック
        $this->validateTenantAccess($attachment);
        
        // 削除権限チェック（アップロード者または管理者のみ）
        $this->validateDeletePermission($attachment);
        
        // ファイル削除実行
        $this->attachmentService->deleteAttachment($attachment);
        
        return response()->noContent();
    }

    /**
     * テナント境界違反チェック
     */
    private function validateTenantAccess(Attachment $attachment): void
    {
        $currentTenantId = Auth::user()->tenant_id;
        
        if ($attachment->tenant_id !== $currentTenantId) {
            throw new TenantViolationException(
                'テナント境界違反: 他のテナントのファイルにアクセスしようとしました。',
                [
                    'user_tenant_id' => $currentTenantId,
                    'attachment_tenant_id' => $attachment->tenant_id,
                    'attachment_id' => $attachment->id,
                    'user_id' => Auth::id()
                ]
            );
        }
    }

    /**
     * ファイル削除権限チェック
     */
    private function validateDeletePermission(Attachment $attachment): void
    {
        $user = Auth::user();
        
        // アップロード者または管理者のみ削除可能
        if ($attachment->uploaded_by !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'ファイル削除権限がありません');
        }
    }
}