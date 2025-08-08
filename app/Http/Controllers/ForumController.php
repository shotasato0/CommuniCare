<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\User;
use App\Models\Forum;
use Illuminate\Support\Facades\Auth;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $forumId = $request->input('forum_id');

        // forum_idがURLパラメータにない場合は、ユーザーのunitに基づいて取得
        if (!$forumId && $user->unit) {
            $forum = Forum::where('unit_id', $user->unit_id)->first();
            $forumId = $forum->id ?? null;
        }

        // forum_idが取得できない場合のエラーハンドリング
        if (!$forumId) {
            return Inertia::render('Forum', [
                'errorMessage' => 'ユニットに所属していません。管理者に確認してください。',
                'posts' => [],
                'units' => Unit::orderBy('sort_order')->with('forum')->get(),
                'users' => User::all(),
                'selectedForumId' => null,
            ]);
        }

        // 検索結果の表示状態
        $search = $request->input('search');
        $query = Post::with([
            'user',
            'quotedPost' => function($query) {
                $query->select('id', 'user_id', 'message', 'title');
            },
            'quotedPost.user', // 引用元の投稿とそのユーザーを取得
            'comments' => function ($query) use ($user) {
                $query->whereNull('parent_id')
                    ->with(['children.user', 'user'])
                ->withCount('likes') // コメントのいいね数を取得
                ->with(['likes' => function ($query) use ($user) {
                    $query->where('user_id', $user->id); // ユーザーのコメントに対するいいねを取得
                }]);
        }])
        ->withCount('likes') // 投稿のいいね数を取得
        ->with(['likes' => function ($query) use ($user) {
            $query->where('user_id', $user->id); // ユーザーの投稿に対するいいねを取得
        }])
        ->where('forum_id', $forumId); // 指定された掲示板内のみを対象

        // 検索クエリがある場合、タイトルとメッセージで検索
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%');
            });
        }

        // 検索結果の投稿をページネーションで取得し、必要なデータを整形
        $posts = $query->latest()->paginate(5)
            ->appends(['forum_id' => $forumId, 'search' => $search])  // ページネーションのクエリパラメータとしてforum_idとsearchをURLに追加
            ->through(function ($post) use ($user) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'message' => $post->message,
                'formatted_message' => $post->formatted_message, //ここでモデルで定義したアクセサを適用
                'img' => $post->img,
                'created_at' => $post->created_at,
                'user' => $post->user,
                'like_count' => $post->likes_count, // 投稿のいいね数
                'is_liked_by_user' => $post->likes->isNotEmpty(), // ユーザーが投稿にいいねしているか
                'quoted_post_deleted' => $post->quoted_post_deleted, // 投稿データのフラグをそのまま使用
                // 引用元の投稿データを取得
                'quoted_post' => $post->quotedPost ? [
                    'id' => $post->quotedPost->id,
                    'message' => $post->quotedPost->trashed() ? null : $post->quotedPost->message,
                    'formatted_message' => $post->quotedPost->trashed() ? null : $post->quotedPost->formatted_message, //ここでモデルで定義したアクセサを適用
                    'title' => $post->quotedPost->trashed() ? null : $post->quotedPost->title,
                    'user' => $post->quotedPost->trashed() ? null : $post->quotedPost->user,
                ] : null,
                // コメントデータをフォーマット
                'comments' => $post->comments->map(fn($comment) => $this->formatComment($comment, $user)),
            ];
        });

        return Inertia::render('Forum', [
            'posts' => $posts,
            'units' => Unit::orderBy('sort_order')->with('forum')->get(),
            'users' => User::all(),
            'selectedForumId' => $forumId,
            'errorMessage' => null,
            'search' => $search,
            // デバッグ用ログ
            'debugPosts' => $posts->toArray(),
        ]);
    }

    // コメントデータをフォーマット
    private function formatComment($comment, $user) {
        return [
            'id' => $comment->id,
            'message' => $comment->message,
            'formatted_message' => $comment->formatted_message,
            'img' => $comment->img,
            'created_at' => $comment->created_at,
            'user' => $comment->user,
            'like_count' => $comment->likes_count,
            'is_liked_by_user' => $comment->likes->isNotEmpty(),
            'children' => $comment->children->map(fn($child) => $this->formatComment($child, $user)), // 再帰的に適用
        ];
    }
}
