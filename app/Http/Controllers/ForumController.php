<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\User;
use App\Models\Forum;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
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
                'units' => Unit::with('forum')->get(),
                'users' => User::all(),
                'selectedForumId' => null,
            ]);
        }

        // 検索結果の表示状態
        $search = $request->input('search');
        $query = Post::with(['user', 'comments' => function ($query) {
            $query->whereNull('parent_id')->with(['children.user', 'user']);
        }])->where('forum_id', $forumId); // 指定された掲示板内のみを対象

        // 検索クエリがある場合、タイトルとメッセージで検索
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('message', 'like', '%' . $search . '%');
            });
    }

        // 検索結果の投稿をページネーションで取得
        $posts = $query->latest()->paginate(5);

        return Inertia::render('Forum', [
            'posts' => $posts,
            'units' => Unit::with('forum')->get(),
            'users' => User::all(),
            'selectedForumId' => $forumId,
            'errorMessage' => null,
            'search' => $search,
        ]);
    }
}
