<?php

use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function toggleLike(Request $request)
    {
        $user = Auth::user();
        $likeableId = $request->input('likeable_id');
        $likeableType = $request->input('likeable_type');

        // ポリモーフィックリレーションのため、対象モデルを動的に設定
        $likeableModel = 'App\\Models\\' . $likeableType;
        $likeable = $likeableModel::findOrFail($likeableId);

        // 既にいいねしているかを確認
        $existingLike = Like::where('user_id', $user->id)
            ->where('likeable_id', $likeableId)
            ->where('likeable_type', $likeableModel)
            ->first();

        if ($existingLike) {
            // 既存のいいねがあれば削除
            $existingLike->delete();
            $isLiked = false;
        } else {
            // 新しいいいねを作成
            $likeable->likes()->create(['user_id' => $user->id]);
            $isLiked = true;
        }

        return response()->json(['isLiked' => $isLiked]);
    }
}
