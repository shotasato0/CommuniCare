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
        $comment = Comment::find($id);
        if (!$comment) {
            return redirect()->back()->withErrors(['comment_not_found' => 'コメントが見つかりません']);
        }

        $comment->delete();

        return redirect()->back();
    }
}
