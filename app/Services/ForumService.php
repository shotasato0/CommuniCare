<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Unit;
use App\Models\User;
use App\Models\Forum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Traits\SecurityValidationTrait;
use App\Traits\TenantBoundaryCheckTrait;

class ForumService
{
    use SecurityValidationTrait, TenantBoundaryCheckTrait;
    /**
     * フォーラムのデータを取得し、整形して返す
     */
    public function getForumData(Request $request): array
    {
        $user = Auth::user();
        $forumId = $this->determineForumId($request, $user);

        if (!$forumId) {
            return $this->buildErrorResponse();
        }

        $search = $request->input('search');
        $posts = $this->getFormattedPosts($forumId, $search, $user);
        
        return $this->buildSuccessResponse($posts, $forumId, $search);
    }

    /**
     * フォーラムIDを決定する
     */
    private function determineForumId(Request $request, $user): ?int
    {
        $forumId = $request->input('forum_id');

        if (!$forumId && $user->unit) {
            $forum = Forum::where('unit_id', $user->unit_id)->first();
            $forumId = $forum->id ?? null;
        }

        return $forumId;
    }

    /**
     * 整形された投稿データを取得
     */
    private function getFormattedPosts(int $forumId, ?string $search, $user): LengthAwarePaginator
    {
        $query = $this->buildPostQuery($forumId, $user);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%');
            });
        }

        $paginator = $query->latest()->paginate(5);
        $paginator->appends(['forum_id' => $forumId, 'search' => $search]);

        return $this->transformPosts($paginator, $user);
    }

    /**
     * 投稿クエリを構築
     */
    private function buildPostQuery(int $forumId, $user)
    {
        return Post::with([
            'user',
            'quotedPost' => function($query) {
                $query->select('id', 'user_id', 'message', 'title');
            },
            'quotedPost.user',
            'comments' => function ($query) use ($user) {
                $query->whereNull('parent_id')
                    ->with(['children.user', 'user'])
                    ->withCount('likes')
                    ->with(['likes' => function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    }]);
            }])
            ->withCount('likes')
            ->with(['likes' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->where('forum_id', $forumId);
    }

    /**
     * 投稿データを変換
     */
    private function transformPosts(LengthAwarePaginator $paginator, $user): LengthAwarePaginator
    {
        $transformedItems = array_map(function ($post) use ($user) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'message' => $post->message,
                'formatted_message' => $post->formatted_message,
                'img' => $post->img,
                'created_at' => $post->created_at,
                'user' => $post->user,
                'like_count' => $post->likes_count,
                'is_liked_by_user' => $post->likes->isNotEmpty(),
                'quoted_post_deleted' => $post->quoted_post_deleted,
                'quoted_post' => $this->formatQuotedPost($post->quotedPost),
                'comments' => $post->comments->map(fn($comment) => $this->formatComment($comment, $user)),
            ];
        }, $paginator->items());

        return new LengthAwarePaginator(
            $transformedItems,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * 引用投稿をフォーマット
     */
    private function formatQuotedPost($quotedPost): ?array
    {
        if (!$quotedPost) {
            return null;
        }

        return [
            'id' => $quotedPost->id,
            'message' => $quotedPost->trashed() ? null : $quotedPost->message,
            'formatted_message' => $quotedPost->trashed() ? null : $quotedPost->formatted_message,
            'title' => $quotedPost->trashed() ? null : $quotedPost->title,
            'user' => $quotedPost->trashed() ? null : $quotedPost->user,
        ];
    }

    /**
     * コメントデータをフォーマット
     */
    private function formatComment($comment, $user): array
    {
        return [
            'id' => $comment->id,
            'message' => $comment->message,
            'formatted_message' => $comment->formatted_message,
            'img' => $comment->img,
            'created_at' => $comment->created_at,
            'user' => $comment->user,
            'like_count' => $comment->likes_count,
            'is_liked_by_user' => $comment->likes->isNotEmpty(),
            'children' => $comment->children->map(fn($child) => $this->formatComment($child, $user)),
        ];
    }

    /**
     * エラーレスポンスを構築
     */
    private function buildErrorResponse(): array
    {
        return [
            'errorMessage' => 'ユニットに所属していません。管理者に確認してください。',
            'posts' => [],
            'units' => Unit::orderBy('sort_order')->with('forum')->get(),
            'users' => User::all(),
            'selectedForumId' => null,
            'userUnitId' => Auth::user()->unit_id, // ユーザーの部署IDを追加
        ];
    }

    /**
     * 成功レスポンスを構築
     */
    private function buildSuccessResponse(LengthAwarePaginator $posts, int $forumId, ?string $search): array
    {
        $posts->appends(['forum_id' => $forumId, 'search' => $search]);

        return [
            'posts' => $posts,
            'units' => Unit::orderBy('sort_order')->with('forum')->get(),
            'users' => User::all(),
            'selectedForumId' => $forumId,
            'errorMessage' => null,
            'search' => $search,
            'userUnitId' => Auth::user()->unit_id, // ユーザーの部署IDを追加
        ];
    }
}