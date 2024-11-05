<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\User;

class ForumController extends Controller
{
    public function showWelcome()
    {
        // 「Welcome」フォーラムを取得
        $welcomeForum = Forum::where('name', 'Welcome')->firstOrFail();

        // ビューにフォーラムデータを渡す
        return view('forums.welcome', ['forum' => $welcomeForum]);
    }


    public function index(Request $request)
{
    $forumId = $request->input('forum_id'); // URLから選択されたユニットIDを取得
    $search = $request->input('search');

    // 投稿データをユニットIDで絞り込む
    $query = Post::with(['user', 'comments' => function ($query) {
        $query->whereNull('parent_id')->with(['children.user', 'user']);
    }]);

    if ($forumId) {
        $query->where('forum_id', $forumId);
    }

    // 検索がある場合
    if ($search) {
        $query->where('title', 'like', '%' . $search . '%')
              ->orWhere('message', 'like', '%' . $search . '%');
    }

    $posts = $query->latest()->paginate(5);

    $units = Unit::with('forum')->get(); // サイドバーに表示するユニット情報
    $users = User::all();

    return Inertia::render('Forum', [
        'posts' => $posts,
        'units' => $units,
        'users' => $users,
        'selectedForumId' => $forumId,
    ]);
    }
}
