<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\PostStoreRequest;
use App\Services\PostService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\Custom\TenantViolationException;
use App\Exceptions\Custom\PostOwnershipException;

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
            Log::error('投稿の作成に失敗しました', ['exception' => $e, 'message' => $e->getMessage()]);
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
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反による投稿削除試行', $e->getLogContext());
            return redirect()->route('forum.index')
                ->with('error', $e->getUserMessage());
        } catch (PostOwnershipException $e) {
            Log::warning('投稿所有権違反による削除試行', $e->getLogContext());
            return redirect()->route('forum.index')
                ->with('error', $e->getUserMessage());
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning('投稿削除の権限エラー', ['exception' => $e, 'user_id' => Auth::id(), 'post_id' => $id, 'message' => $e->getMessage()]);
            return redirect()->route('forum.index')
                ->with('error', 'この投稿を削除する権限がありません。');
        } catch (\Exception $e) {
            Log::error('投稿の削除に失敗しました', ['exception' => $e, 'post_id' => $id, 'message' => $e->getMessage()]);
            return redirect()->route('forum.index')
                ->with('error', '投稿の削除に失敗しました。');
        }
    }
}
