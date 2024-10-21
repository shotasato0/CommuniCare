<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
{
    $search = request()->input('search');

    // 投稿を取得
    $posts = Post::with([
        'user', // 投稿者のユーザー情報を取得
        'comments' => function ($query) {
            $query->whereNull('parent_id') // 親コメントのみ取得
                  ->with(['children.user', 'user']); // 子コメントと再帰的に子コメントを取得
        }
    ])
    // 検索ワードがあれば、タイトルまたはメッセージに含まれる投稿を取得
    ->when($search, function ($query, $search) {
        return $query->where('title', 'like', '%' . $search . '%')
    ->orWhere('message', 'like', '%' . $search . '%');
    })
    ->latest()
    ->paginate(5);

    return Inertia::render('Forum', [
        'posts' => $posts,
        'search' => $search, // 検索ワードを渡す
    ]);
}

    public function store(Request $request)
{
    // バリデーション
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    // 投稿者のuser_idと投稿内容を保存
    $post = Post::create([
        'user_id' => auth()->id(),  // ログイン中のユーザーのIDを保存
        'title' => $validated['title'],
        'message' => $validated['message'],
    ]);

    // 新しい投稿にユーザー情報をロードして返す
    $post->load('user'); // リレーションをロードする

       // 正しいInertiaレスポンスで新しい投稿とauth情報を返す
       return Inertia::render('Forum', [
        'newPost' => $post,
        'auth' => auth()->user(), // ログインユーザー情報も渡す
    ]);
}


    public function destroy($id)
    {
        Post::findOrFail($id)->delete();
        return redirect()->route('forum.index');
    }
}
