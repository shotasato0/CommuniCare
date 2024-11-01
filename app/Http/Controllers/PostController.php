<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\Unit;

class PostController extends Controller
{

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

    $units = Unit::all();

       // 正しいInertiaレスポンスで新しい投稿とauth情報を返す
       return Inertia::render('Forum', [
        'newPost' => $post,
        'auth' => auth()->user(), // ログインユーザー情報も渡す
        'units' => $units,
    ]);
}


    public function destroy($id)
    {
        Post::findOrFail($id)->delete();
        return redirect()->route('forum.index');
    }
}
