<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\User;

class PostController extends Controller
{
    public function index()
    {
        // ユーザー情報を含めた投稿データを取得
        $posts = Post::with(['user','comments.user'])
        ->latest()
        ->get();

        $users = User::all(['id', 'name']);

        return Inertia::render('Forum', [
            'posts' => $posts,
            'users' => $users,
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

    $users = User::all(['id', 'name']);

       // 正しいInertiaレスポンスで新しい投稿とauth情報を返す
       return Inertia::render('Forum', [
        'newPost' => $post,
        'auth' => auth()->user(), // ログインユーザー情報も渡す
        'users' => $users,
    ]);
}


    public function destroy($id)
    {
        Post::findOrFail($id)->delete();
        return redirect()->route('forum.index');
    }
}
