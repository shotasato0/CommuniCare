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
            'title' => $this->getTitleValidationRule(),
            'message' => 'required|string',
            'forum_id' => 'required|exists:forums,id',
            'quoted_post_id' => 'nullable|exists:posts,id',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            // 統一ファイル添付システム対応
            'files' => 'nullable|array|max:10',
            'files.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv|max:10240',
        ];
    }

    /**
     * タイトルの検証ルールを取得
     * 引用投稿がある場合はタイトルを任意とし、ない場合は必須とする
     *
     * @return string
     */
    private function getTitleValidationRule()
    {
        return $this->has('quoted_post_id') && $this->filled('quoted_post_id')
            ? 'nullable|string|max:255'
            : 'required|string|max:255';
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
            // 統一ファイル添付システム用メッセージ
            'files.array' => 'ファイルは配列で送信してください。',
            'files.max' => '一度に添付できるファイルは最大10個までです。',
            'files.*.file' => 'ファイルが正しく選択されていません。',
            'files.*.mimes' => 'サポートされていないファイル形式です。画像・PDF・文書ファイルのみアップロード可能です。',
            'files.*.max' => 'ファイルサイズは10MB以下にしてください。',
        ];
    }
}
