<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 投稿作成リクエスト
 */
class PostStoreRequest extends FormRequest
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
            'title' => $this->input('quoted_post_id') ? 'nullable|string|max:255' : 'required|string|max:255',
            'message' => 'required|string',
            'forum_id' => 'required|exists:forums,id',
            'quoted_post_id' => 'nullable|exists:posts,id',
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
            'title.required' => 'タイトルは必須です。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'message.required' => 'メッセージは必須です。',
            'message.string' => 'メッセージは文字列で入力してください。',
            'forum_id.required' => 'フォーラムの指定は必須です。',
            'forum_id.exists' => '指定されたフォーラムは存在しません。',
            'quoted_post_id.exists' => '引用対象の投稿が存在しません。',
            'img.image' => 'ファイルは画像である必要があります。',
            'img.mimes' => 'jpeg、png、jpg、gif形式のファイルのみアップロード可能です。',
            'img.max' => 'ファイルサイズは10MB以下にしてください。',
        ];
    }
}