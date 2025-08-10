<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\PostStoreRequest;
use App\Services\PostService;

class PostController extends Controller
{
    private PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function store(PostStoreRequest $request)
    {
        try {
            $this->postService->createPost($request);
            
            return redirect()->route('forum.index')
                ->with('success', '投稿を作成しました。');
        } catch (\Exception $e) {
            return redirect()->route('forum.index')
                ->with('error', '投稿の作成に失敗しました。');
        }
    }

    public function destroy($id)
    {
        try {
            $this->postService->deletePost($id);
            
            return redirect()->route('forum.index')
                ->with('success', '投稿を削除しました。');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('forum.index')
                ->with('error', 'この投稿を削除する権限がありません。');
        } catch (\Exception $e) {
            return redirect()->route('forum.index')
                ->with('error', '投稿の削除に失敗しました。');
        }
    }
}
