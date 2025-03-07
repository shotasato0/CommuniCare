<?php

namespace App\Http\Controllers;

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

        
        $likeableModel = 'App\\Models\\' . ucfirst($likeableType); // モデル名を取得
        $likeable = $likeableModel::findOrFail($likeableId); // 指定されたIDのモデルを取得

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
            $likeable->likes()->create(['tenant_id' => $user->tenant_id, 'user_id' => $user->id]);
            $isLiked = true;
        }

        return response()->json(['isLiked' => $isLiked]);
    }

    // いいねしたユーザーを取得
    public function getLikedUsers($type, $id)
    {
        $likeableModel = 'App\\Models\\' . ucfirst(rtrim($type, 's')); // モデル名を取得
        $likeable = $likeableModel::findOrFail($id); // 指定されたIDのモデルを取得

        // いいねしたユーザーの名前一覧を取得
        $likedUsers = $likeable->likes()->with('user')->get()->pluck('user.name');

        return response()->json($likedUsers);
    }


}
