<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Comment\CommentStoreRequest;
use App\Exceptions\Custom\TenantViolationException;

class CommentController extends Controller
{
    private CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }
    public function store(CommentStoreRequest $request)
    {
        try {
            $comment = $this->commentService->createComment($request);
            $post = $comment->post;
            
            $user = Auth::user();
            $redirectParams = ['forum_id' => $post->forum_id];
            
            // ユーザーが部署に所属している場合、active_unit_idも追加
            if ($user->unit_id) {
                $redirectParams['active_unit_id'] = $user->unit_id;
            }
            
            return redirect()->route('forum.index', $redirectParams)
                ->with('success', 'コメントを投稿しました。');
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反によるコメント作成試行', $e->getLogContext());
            return redirect()->route('forum.index')
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            Log::error('コメントの作成に失敗しました', ['exception' => $e, 'message' => $e->getMessage()]);
            return redirect()->route('forum.index')
                ->with('error', 'コメントの作成に失敗しました。');
        }
    }

    public function destroy($id)
    {
        try {
            $this->commentService->deleteComment($id);
            
            return redirect()->back()
                ->with('success', 'コメントを削除しました。');
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反によるコメント削除試行', $e->getLogContext());
            return redirect()->back()
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            Log::error('コメントの削除に失敗しました', ['exception' => $e, 'comment_id' => $id, 'message' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'コメントの削除に失敗しました。');
        }
    }
    
    /**
     * 既存のコメントにファイルを追加
     */
    public function addAttachments(Request $request, Comment $comment)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:jpeg,png,jpg,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv|max:10240',
        ]);
        
        try {
            $attachments = $this->commentService->addAttachmentsToComment(
                $comment,
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
            Log::error('ファイル添付に失敗', ['comment_id' => $comment->id, 'exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'ファイルの添付に失敗しました。'
            ], 500);
        }
    }
    
    /**
     * コメントからファイルを削除
     */
    public function removeAttachment(Comment $comment, int $attachmentId)
    {
        try {
            $this->commentService->removeAttachmentFromComment($comment, $attachmentId);
            
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
            Log::error('ファイル削除に失敗', ['comment_id' => $comment->id, 'attachment_id' => $attachmentId, 'exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'ファイルの削除に失敗しました。'
            ], 500);
        }
    }
}
