<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use App\Http\Requests\AttachmentStoreRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\AttachmentOwnershipException;

class AttachmentController extends Controller
{
    private AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * 複数ファイルアップロード（統一API）
     * 
     * POST /api/attachments
     * 
     * 対応形式：画像・文書・音声
     * 制限：最大10MB・10ファイル同時
     */
    public function store(AttachmentStoreRequest $request): JsonResponse
    {
        try {
            $files = $request->file('files', []);
            $attachableType = $request->input('attachable_type');
            $attachableId = $request->input('attachable_id');

            // 複数ファイル処理
            $attachments = $this->attachmentService->uploadFiles(
                $files, 
                $attachableType, 
                $attachableId
            );

            return response()->json([
                'success' => true,
                'message' => count($attachments) . '件のファイルをアップロードしました。',
                'attachments' => $attachments,
                'count' => count($attachments)
            ], 201);

        } catch (TenantViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
                'error' => 'tenant_violation'
            ], 403);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'validation_error'
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ファイルのアップロードに失敗しました。',
                'error' => 'upload_failed'
            ], 500);
        }
    }

    /**
     * 添付ファイル詳細取得
     * 
     * GET /api/attachments/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            
            $attachment = Attachment::where('id', $id)
                ->where('tenant_id', $currentUser->tenant_id)
                ->first();

            if (!$attachment) {
                return response()->json([
                    'success' => false,
                    'message' => '添付ファイルが見つかりません。',
                    'error' => 'not_found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'attachment' => [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'file_size' => $attachment->file_size,
                    'formatted_file_size' => $attachment->formatted_file_size,
                    'file_type' => $attachment->file_type,
                    'mime_type' => $attachment->mime_type,
                    'file_extension' => $attachment->file_extension,
                    'is_image' => $attachment->isImage(),
                    'is_document' => $attachment->isDocument(),
                    'is_downloadable' => $attachment->isDownloadable(),
                    'created_at' => $attachment->created_at,
                    'upload_url' => route('attachments.download', $attachment->id)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '添付ファイル情報の取得に失敗しました。',
                'error' => 'fetch_failed'
            ], 500);
        }
    }

    /**
     * 添付ファイル削除
     * 
     * DELETE /api/attachments/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->attachmentService->deleteAttachment($id);
            
            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => '添付ファイルの削除権限がないか、ファイルが見つかりません。',
                    'error' => 'delete_failed'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => '添付ファイルを削除しました。'
            ]);

        } catch (TenantViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
                'error' => 'tenant_violation'  
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '添付ファイルの削除に失敗しました。',
                'error' => 'delete_failed'
            ], 500);
        }
    }

    /**
     * 添付ファイルダウンロード（認証付き）
     * 
     * GET /attachments/{id}/download
     */
    public function download(int $id)
    {
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            
            $attachment = Attachment::where('id', $id)
                ->where('tenant_id', $currentUser->tenant_id)
                ->first();

            if (!$attachment) {
                abort(404, '添付ファイルが見つかりません。');
            }

            if (!$attachment->isDownloadable()) {
                abort(403, 'このファイルはダウンロードできません。');
            }

            $filePath = storage_path('app/public/' . $attachment->file_path);
            
            if (!file_exists($filePath)) {
                abort(404, 'ファイルが存在しません。');
            }

            return response()->file($filePath, [
                'Content-Type' => $attachment->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $attachment->original_name . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            abort(500, 'ファイルのダウンロードに失敗しました。');
        }
    }

    /**
     * 添付ファイル一覧取得（関連モデル別）
     * 
     * GET /api/attachments?attachable_type=App\Models\Post&attachable_id=1
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();

            $query = Attachment::where('tenant_id', $currentUser->tenant_id)
                ->orderBy('created_at', 'desc');

            // 関連モデル絞り込み
            if ($request->has('attachable_type') && $request->has('attachable_id')) {
                $query->where('attachable_type', $request->input('attachable_type'))
                      ->where('attachable_id', $request->input('attachable_id'));
            }

            // ファイルタイプ絞り込み
            if ($request->has('file_type')) {
                $query->where('file_type', $request->input('file_type'));
            }

            $attachments = $query->paginate(20);

            return response()->json([
                'success' => true,
                'attachments' => $attachments->items(),
                'pagination' => [
                    'current_page' => $attachments->currentPage(),
                    'last_page' => $attachments->lastPage(),
                    'total' => $attachments->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '添付ファイル一覧の取得に失敗しました。',
                'error' => 'fetch_failed'
            ], 500);
        }
    }
}