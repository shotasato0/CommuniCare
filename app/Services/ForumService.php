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
     * 投稿クエリを構築（テナント境界チェック強化・N+1対策）
     */
    private function buildPostQuery(int $forumId, $user)
    {
        return Post::where('forum_id', $forumId)
            ->where('tenant_id', $user->tenant_id) // テナント境界チェック
            ->with([
                'user' => function($query) use ($user) {
                    $query->select('id', 'name', 'tenant_id')
                          ->where('tenant_id', $user->tenant_id);
                },
                'quotedPost' => function($query) use ($user) {
                    $query->select('id', 'user_id', 'message', 'title', 'tenant_id', 'img')
                          ->where('tenant_id', $user->tenant_id)
                          ->with([
                              'user' => function($query) use ($user) {
                                  $query->select('id', 'name', 'tenant_id')
                                        ->where('tenant_id', $user->tenant_id);
                              },
                              'attachments' => function($query) use ($user) {
                                  $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_path', 'file_type', 'tenant_id')
                                        ->where('tenant_id', $user->tenant_id)
                                        ->where('file_type', 'image');
                              }
                          ]);
                },
                'comments' => function ($query) use ($user) {
                    $query->select('id', 'post_id', 'user_id', 'message', 'parent_id', 'tenant_id', 'img', 'created_at')
                          ->where('tenant_id', $user->tenant_id)
                          ->whereNull('parent_id')
                          ->with([
                              'user' => function($query) use ($user) {
                                  $query->select('id', 'name', 'tenant_id')
                                        ->where('tenant_id', $user->tenant_id);
                              },
                              'attachments' => function($query) use ($user) {
                                  $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_path', 'file_type', 'tenant_id')
                                        ->where('tenant_id', $user->tenant_id)
                                        ->where('file_type', 'image');
                              },
                              'children' => function($query) use ($user) {
                                  $query->select('id', 'post_id', 'user_id', 'message', 'parent_id', 'tenant_id', 'img', 'created_at')
                                        ->where('tenant_id', $user->tenant_id)
                                        ->with([
                                            'user' => function($query) use ($user) {
                                                $query->select('id', 'name', 'tenant_id')
                                                      ->where('tenant_id', $user->tenant_id);
                                            },
                                            'attachments' => function($query) use ($user) {
                                                $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_path', 'file_type', 'tenant_id')
                                                      ->where('tenant_id', $user->tenant_id)
                                                      ->where('file_type', 'image');
                                            }
                                        ]);
                              },
                              'likes' => function ($query) use ($user) {
                                  $query->select('id', 'likeable_id', 'likeable_type', 'user_id', 'tenant_id')
                                        ->where('tenant_id', $user->tenant_id)
                                        ->where('user_id', $user->id);
                              }
                          ])
                          ->withCount(['likes' => function($query) use ($user) {
                              $query->where('tenant_id', $user->tenant_id);
                          }]);
                },
                'likes' => function ($query) use ($user) {
                    $query->select('id', 'likeable_id', 'likeable_type', 'user_id', 'tenant_id')
                          ->where('tenant_id', $user->tenant_id)
                          ->where('user_id', $user->id);
                },
                'attachments' => function($query) use ($user) {
                    $query->select('id', 'attachable_id', 'attachable_type', 'original_name', 'file_path', 'file_type', 'tenant_id')
                          ->where('tenant_id', $user->tenant_id)
                          ->where('file_type', 'image');
                }
            ])
            ->withCount(['likes' => function($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            }])
            ->select('id', 'user_id', 'forum_id', 'title', 'message', 'quoted_post_id', 'tenant_id', 'img', 'like_count', 'quoted_post_deleted', 'created_at', 'updated_at');
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
                'img' => $post->img, // 後方互換性
                'attachments' => $post->attachments ?? [], // 新Attachmentシステム
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
     * 引用投稿をフォーマット（Attachmentシステム対応）
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
            'img' => $quotedPost->trashed() ? null : $quotedPost->img, // 後方互換性
            'attachments' => $quotedPost->trashed() ? [] : ($quotedPost->attachments ?? []), // 新Attachmentシステム
            'user' => $quotedPost->trashed() ? null : $quotedPost->user,
        ];
    }

    /**
     * コメントデータをフォーマット（Attachmentシステム対応）
     */
    private function formatComment($comment, $user): array
    {
        return [
            'id' => $comment->id,
            'message' => $comment->message,
            'formatted_message' => $comment->formatted_message,
            'img' => $comment->img, // 後方互換性のため保持
            'attachments' => $comment->attachments ?? [], // 新Attachmentシステム
            'created_at' => $comment->created_at,
            'user' => $comment->user,
            'like_count' => $comment->likes_count,
            'is_liked_by_user' => $comment->likes->isNotEmpty(),
            'children' => $comment->children->map(fn($child) => $this->formatComment($child, $user)),
        ];
    }

    /**
     * エラーレスポンスを構築（テナント境界チェック強化）
     */
    private function buildErrorResponse(): array
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        return [
            'errorMessage' => 'ユニットに所属していません。管理者に確認してください。',
            'posts' => [],
            'units' => Unit::where('tenant_id', $currentUser->tenant_id)
                         ->orderBy('sort_order')
                         ->with(['forum' => function($query) use ($currentUser) {
                             $query->where('tenant_id', $currentUser->tenant_id);
                         }])
                         ->get(),
            'users' => User::where('tenant_id', $currentUser->tenant_id)
                         ->select('id', 'name', 'tenant_id')
                         ->get(),
            'selectedForumId' => null,
            'userUnitId' => $currentUser->unit_id,
        ];
    }

    /**
     * 成功レスポンスを構築（テナント境界チェック強化）
     */
    private function buildSuccessResponse(LengthAwarePaginator $posts, int $forumId, ?string $search): array
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        
        $posts->appends(['forum_id' => $forumId, 'search' => $search]);

        return [
            'posts' => $posts,
            'units' => Unit::where('tenant_id', $currentUser->tenant_id)
                         ->orderBy('sort_order')
                         ->with(['forum' => function($query) use ($currentUser) {
                             $query->where('tenant_id', $currentUser->tenant_id);
                         }])
                         ->get(),
            'users' => User::where('tenant_id', $currentUser->tenant_id)
                         ->select('id', 'name', 'tenant_id')
                         ->get(),
            'selectedForumId' => $forumId,
            'errorMessage' => null,
            'search' => $search,
            'userUnitId' => $currentUser->unit_id,
        ];
    }
}