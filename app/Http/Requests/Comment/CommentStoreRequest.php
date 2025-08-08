<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * コメント作成リクエスト
 */
class CommentStoreRequest extends FormRequest
{
    /**
     * 認可
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array
     */
    public function rules()
    {
        return [
            'post_id' => 'required|exists:posts,id',
            'parent_id' => 'nullable|exists:comments,id',
            'message' => 'required|string|max:1000',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ];
    }

    /**
     * エラーメッセージ
     *
     * @return array
     */
    public function messages()
    {
        return [
            'post_id.required' => '投稿の指定は必須です。',
            'post_id.exists' => '指定された投稿は存在しません。',
            'parent_id.exists' => '返信対象のコメントが存在しません。',
            'message.required' => 'メッセージは必須です。',
            'message.string' => 'メッセージは文字列で入力してください。',
            'message.max' => 'メッセージは1000文字以内で入力してください。',
            'img.image' => 'ファイルは画像である必要があります。',
            'img.mimes' => 'jpeg、png、jpg、gif形式のファイルのみアップロード可能です。',
            'img.max' => 'ファイルサイズは10MB以下にしてください。',
        ];
    }
}