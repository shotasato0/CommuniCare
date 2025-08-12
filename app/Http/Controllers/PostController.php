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
            $post = $this->postService->createPost($request);
            $user = Auth::user();
            
            // 投稿作成後、適切なパラメータでフォーラムにリダイレクト
            $redirectParams = [
                'forum_id' => $post->forum_id,
            ];
            
            // ユーザーが部署に所属している場合、active_unit_idも追加
            if ($user->unit_id) {
                $redirectParams['active_unit_id'] = $user->unit_id;
            }
            
            return redirect()->route('forum.index', $redirectParams)
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
            // 削除前に投稿情報を取得（リダイレクト用）
            $post = $this->postService->getPostById($id);
            $forumId = $post->forum_id;
            
            $this->postService->deletePost($id);
            
            $user = Auth::user();
            $redirectParams = ['forum_id' => $forumId];
            
            // ユーザーが部署に所属している場合、active_unit_idも追加
            if ($user->unit_id) {
                $redirectParams['active_unit_id'] = $user->unit_id;
            }
            
            return redirect()->route('forum.index', $redirectParams)
                ->with('success', '投稿を削除しました。');
        } catch (TenantViolationException $e) {
            Log::critical('テナント境界違反による投稿削除試行', $e->getLogContext());
            return redirect()->route('forum.index')
                ->with('error', $e->getUserMessage());
        } catch (PostOwnershipException $e) {
            Log::warning('投稿所有権違反による削除試行', $e->getLogContext());
            return redirect()->route('forum.index')
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            Log::error('投稿の削除に失敗しました', ['exception' => $e, 'post_id' => $id, 'message' => $e->getMessage()]);
            return redirect()->route('forum.index')
                ->with('error', '投稿の削除に失敗しました。');
        }
    }
}
