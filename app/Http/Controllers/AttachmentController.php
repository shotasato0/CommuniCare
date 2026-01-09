<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
    public function show($attachment): StreamedResponse
    {
        // ルートモデルバインディング時のテナントスコープ影響を避け、明示的に取得
        $attachment = Attachment::withoutGlobalScopes()->findOrFail($attachment);

        // テナント境界チェック
        $this->validateTenantAccess($attachment);

        // デバッグログ（保存/表示のFSコンテキスト不一致確認用）
        if (config('attachments.debug_log')) try {
            $host = request()->getHost();
            $userTenantId = optional(Auth::user())->tenant_id;
            $currentTenantId = function_exists('tenant') && tenant() ? tenant()->id : null;
            $diskRoot = config('filesystems.disks.public.root');
            $path = $attachment->file_path;
            $exists = Storage::disk('public')->exists($path);
            Log::info('Attachment show debug', [
                'host' => $host,
                'env' => config('app.env'),
                'route' => optional(request()->route())->getName(),
                'user_tenant_id' => $userTenantId,
                'current_tenant_id' => $currentTenantId,
                'attachment_id' => $attachment->id,
                'attachment_tenant_id' => $attachment->tenant_id,
                'disk_root' => $diskRoot,
                'file_path' => $path,
                'exists' => $exists,
            ]);
        } catch (\Throwable $e) {
            // ログ取得で例外が出ても本処理は継続
        }
        
        // ファイル存在チェック + self-heal（任意）
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            if (config('attachments.self_heal')) {
                // tenant FS になければ中央FSからの自己修復を試行
                $tenant = function_exists('tenant') ? tenant() : null;
                try {
                    if ($tenant) tenancy()->end();
                    $centralHas = Storage::disk('public')->exists($attachment->file_path);
                    $content = $centralHas ? Storage::disk('public')->get($attachment->file_path) : null;
                    if ($tenant) tenancy()->initialize($tenant);

                    if ($centralHas && $content !== null) {
                        // 親ディレクトリの作成
                        $dir = dirname($attachment->file_path);
                        if (!Storage::disk('public')->exists($dir)) {
                            Storage::disk('public')->makeDirectory($dir);
                        }
                        Storage::disk('public')->put($attachment->file_path, $content);
                        if (config('attachments.debug_log')) {
                            Log::info('Attachment self-heal: copied from central to tenant FS', [
                                'attachment_id' => $attachment->id,
                                'path' => $attachment->file_path,
                                'tenant_id' => optional($tenant)->id,
                            ]);
                        }
                    } else {
                        abort(404, 'ファイルが見つかりません');
                    }
                } catch (\Throwable $e) {
                    if ($tenant) {
                        // 念のためテナントを再初期化
                        try { tenancy()->initialize($tenant); } catch (\Throwable $ignored) {}
                    }
                    Log::error('Attachment self-heal failed', [
                        'attachment_id' => $attachment->id,
                        'path' => $attachment->file_path,
                        'error' => $e->getMessage()
                    ]);
                    abort(404, 'ファイルが見つかりません');
                }
            } else {
                abort(404, 'ファイルが見つかりません');
            }
        }
        
        // ファイルストリーミング応答（StreamedResponseで返却）
        $stream = Storage::disk('public')->readStream($attachment->file_path);
        if ($stream === false) {
            abort(404, 'ファイルが見つかりません');
        }
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'public, max-age=31536000',
            'Expires' => now()->addYear()->toRfc7231String(),
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
        ]);
    }

    /**
     * ファイル削除
     */
    public function destroy($attachment): Response
    {
        // ルートモデルバインディング時のテナントスコープ影響を避け、明示的に取得
        $attachment = Attachment::withoutGlobalScopes()->findOrFail($attachment);

        // テナント境界チェック
        $this->validateTenantAccess($attachment);

        if (config('attachments.debug_log')) try {
            Log::info('Attachment destroy debug', [
                'host' => request()->getHost(),
                'env' => config('app.env'),
                'user_tenant_id' => optional(Auth::user())->tenant_id,
                'current_tenant_id' => (function_exists('tenant') && tenant()) ? tenant()->id : null,
                'attachment_id' => $attachment->id,
                'attachment_tenant_id' => $attachment->tenant_id,
                'file_path' => $attachment->file_path,
            ]);
        } catch (\Throwable $e) {
            // no-op
        }
        
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
                currentTenantId: (string) $currentTenantId,
                resourceTenantId: (string) $attachment->tenant_id,
                resourceType: 'attachment',
                resourceId: (int) $attachment->id,
                message: 'テナント境界違反: 他のテナントのファイルにアクセスしようとしました。'
            );
        }
    }

    /**
     * ファイル削除権限チェック
     */
    private function validateDeletePermission(Attachment $attachment): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // アップロード者または管理者のみ削除可能
        if ($attachment->uploaded_by !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'ファイル削除権限がありません');
        }
    }
}
