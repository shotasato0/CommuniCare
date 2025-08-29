<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\PostStoreRequest;
use App\Services\PostService;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\PostOwnershipException;

class PostController extends Controller
{
    private PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function store(PostStoreRequest $request)
    {
        try {
            // デバッグ情報
            Log::info('=== PostController::store Debug ===', [
                'hasFile_files' => $request->hasFile('files'),
                'hasFile_image' => $request->hasFile('image'),
                'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
                'image_name' => $request->hasFile('image') ? $request->file('image')->getClientOriginalName() : null,
                'all_files' => $request->allFiles()
            ]);
            
            $post = $this->postService->createPost($request);
            $user = Auth::user();
            
            // 投稿作成後、適切なパラメータでフォーラムにリダイレクト
            $redirectParams = [
                'forum_id' => $post->forum_id,
            ];
            
            // ユーザーが部署に所属している場合、active_unit_idも追加
            if ($user->unit_id) {
                $redirectParams['active_unit_id'] = $user->unit_id;
            }
            
            return redirect()->route('forum.index', $redirectParams)
                ->with('success', '投稿を作成しました。');
        } catch (\Exception $e) {
            Log::error('投稿の作成に失敗しました', ['exception' => $e, 'message' => $e->getMessage()]);
            return redirect()->route('forum.index')
                ->with('error', '投稿の作成に失敗しました。');
        }
    }

    public function destroy($id)
    {
        try {
            // 削除前に投稿情報を取得（リダイレクト用）
            $post = $this->postService->getPostById($id);
            $forumId = $post->forum_id;
            
            $this->postService->deletePost($id);
            
            $user = Auth::user();
            $redirectParams = ['forum_id' => $forumId];
            
            // ユーザーが部署に所属している場合、active_unit_idも追加
            if ($user->unit_id) {
                $redirectParams['active_unit_id'] = $user->unit_id;
            }
            
            return redirect()->route('forum.index', $redirectParams)
                ->with('success', '投稿を削除しました。');
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反による投稿削除試行', $e->getLogContext());
            return redirect()->route('forum.index')
                ->with('error', $e->getUserMessage());
        } catch (PostOwnershipException $e) {
            Log::warning('投稿所有権違反による削除試行', $e->getLogContext());
            return redirect()->route('forum.index')
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            Log::error('投稿の削除に失敗しました', ['exception' => $e, 'post_id' => $id, 'message' => $e->getMessage()]);
            return redirect()->route('forum.index')
                ->with('error', '投稿の削除に失敗しました。');
        }
    }
    
    /**
     * 既存の投稿にファイルを追加
     */
    public function addAttachments(Request $request, Post $post)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|mimes:jpeg,png,jpg,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv|max:10240',
        ]);
        
        try {
            $attachments = $this->postService->addAttachmentsToPost(
                $post,
                $request->file('files')
            );
            
            return response()->json([
                'success' => true,
                'message' => count($attachments) . '個のファイルを添付しました。',
                'attachments' => $attachments
            ]);
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反によるファイル添付試行', $e->getLogContext());
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('ファイル添付に失敗', ['post_id' => $post->id, 'exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'ファイルの添付に失敗しました。'
            ], 500);
        }
    }
    
    /**
     * 投稿からファイルを削除
     */
    public function removeAttachment(Post $post, int $attachmentId)
    {
        try {
            $this->postService->removeAttachmentFromPost($post, $attachmentId);
            
            return response()->json([
                'success' => true,
                'message' => 'ファイルを削除しました。'
            ]);
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反によるファイル削除試行', $e->getLogContext());
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('ファイル削除に失敗', ['post_id' => $post->id, 'attachment_id' => $attachmentId, 'exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'ファイルの削除に失敗しました。'
            ], 500);
        }
    }
}
