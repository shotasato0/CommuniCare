<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\Unit;
use App\Http\Requests\Post\PostStoreRequest;

class PostController extends Controller
{

    public function store(PostStoreRequest $request)
    {
        $validated = $request->validated();

        // 画像パスを取得
        $imgPath = null;
        if ($request->hasFile('image')) {
            $imgPath = $request->file('image')->store('images', 'public');
        }

        // 投稿を作成
        $post = Post::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'message' => $validated['message'],
            'forum_id' => $validated['forum_id'],
            'quoted_post_id' => $validated['quoted_post_id'] ?? null,
            'img' => $imgPath
        ]);

        // 掲示板ページにリダイレクト
        return redirect()->route('forum.index');
    }

    public function destroy($id)
    {
        // トランザクションを利用して整合性を確保
        \DB::transaction(function () use ($id) {
            // 削除対象の投稿を取得
            $post = Post::findOrFail($id);

            // 削除対象の投稿を引用している投稿を取得
            $affectedPosts = Post::where('quoted_post_id', $post->id)->get();

            // 引用投稿のフラグを更新
            Post::where('quoted_post_id', $post->id)
                ->update(['quoted_post_deleted' => true]);

            // 更新後の投稿を取得
            $updatedPosts = Post::where('quoted_post_id', $post->id)->get();

            // 削除対象の投稿を削除
            $post->forceDelete();
        });

        return app(ForumController::class)->index(request());
    }


}
