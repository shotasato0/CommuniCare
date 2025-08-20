<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attachment;

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
            
            // 旧システム（後方互換性）
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            
            // 新Attachmentシステム
            'attachment_ids' => 'nullable|array|max:10',
            'attachment_ids.*' => 'integer|exists:attachments,id',
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
            
            // 旧システム
            'img.image' => 'ファイルは画像である必要があります。',
            'img.mimes' => 'jpeg、png、jpg、gif形式のファイルのみアップロード可能です。',
            'img.max' => 'ファイルサイズは10MB以下にしてください。',
            
            // 新Attachmentシステム
            'attachment_ids.array' => '添付ファイルIDは配列形式である必要があります。',
            'attachment_ids.max' => '添付ファイルは最大10個まで選択できます。',
            'attachment_ids.*.integer' => '添付ファイルIDは整数である必要があります。',
            'attachment_ids.*.exists' => '指定された添付ファイルが存在しません。',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 新Attachmentシステム：テナント境界チェック
            if ($this->has('attachment_ids') && is_array($this->input('attachment_ids'))) {
                $this->validateAttachmentTenantBoundary($validator);
            }
        });
    }

    /**
     * 添付ファイルのテナント境界チェック
     */
    private function validateAttachmentTenantBoundary($validator): void
    {
        $attachmentIds = $this->input('attachment_ids', []);
        $currentUser = $this->user();

        if (!$currentUser || empty($attachmentIds)) {
            return;
        }

        try {
            $attachments = Attachment::whereIn('id', $attachmentIds)->get();

            foreach ($attachments as $attachment) {
                if ($attachment->tenant_id !== $currentUser->tenant_id) {
                    $validator->errors()->add(
                        'attachment_ids',
                        "添付ファイル「{$attachment->original_name}」へのアクセス権限がありません。"
                    );
                }

                // アップロード者チェック（任意）
                if ($attachment->uploaded_by !== $currentUser->id && !$currentUser->hasRole('admin')) {
                    $validator->errors()->add(
                        'attachment_ids',
                        "添付ファイル「{$attachment->original_name}」の使用権限がありません。"
                    );
                }
            }
        } catch (\Exception $e) {
            $validator->errors()->add('attachment_ids', '添付ファイルの検証中にエラーが発生しました。');
        }
    }
}