<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attachment;

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
            
            // 旧システム（後方互換性）
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            
            // 新Attachmentシステム（コメントは3ファイルまで）
            'attachment_ids' => 'nullable|array|max:3',
            'attachment_ids.*' => 'integer|exists:attachments,id',
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
            
            // 旧システム
            'img.image' => 'ファイルは画像である必要があります。',
            'img.mimes' => 'jpeg、png、jpg、gif形式のファイルのみアップロード可能です。',
            'img.max' => 'ファイルサイズは10MB以下にしてください。',
            
            // 新Attachmentシステム
            'attachment_ids.array' => '添付ファイルIDは配列形式である必要があります。',
            'attachment_ids.max' => 'コメントには最大3個まで添付ファイルを選択できます。',
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

                // アップロード者チェック
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