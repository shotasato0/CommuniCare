<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        // 投稿と親コメント、その子コメントを取得
        $posts = Post::with([
            'user',
            'comments' => function($query) {
                $query->whereNull('parent_id') // 親コメントのみ取得
                  ->with('children', 'user'); // 子コメントもEager Loading
        },
        'comments.children.user' // 子コメントのユーザー情報も取得
        ])->latest()->get();

        return Inertia::render('Forum', [
            'posts' => $posts
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
