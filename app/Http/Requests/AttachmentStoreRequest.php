<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class AttachmentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check(); // ログイン済みユーザーのみ許可
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // ファイルアップロード（統一要件）
            'files' => ['required', 'array', 'min:1', 'max:10'], // 最大10ファイル同時
            'files.*' => [
                'required',
                'file',
                File::default()
                    ->max(10 * 1024) // 最大10MB
                    ->types([
                        // 画像形式
                        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
                        // 文書形式  
                        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'rtf',
                        // 音声形式（統一要件）
                        'mp3', 'wav', 'ogg', 'm4a'
                    ])
            ],

            // 関連モデル情報
            'attachable_type' => [
                'required', 
                'string',
                'in:App\Models\Post,App\Models\Comment,App\Models\User'
            ],
            'attachable_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'アップロードするファイルを選択してください。',
            'files.array' => 'ファイルの形式が正しくありません。',
            'files.max' => '一度にアップロードできるファイルは最大10件です。',
            
            'files.*.required' => 'ファイルが選択されていません。',
            'files.*.file' => '有効なファイルを選択してください。',
            'files.*.max' => 'ファイルサイズは最大10MBまでです。',
            'files.*.mimes' => 'サポートされていないファイル形式です。',
            
            'attachable_type.required' => '関連モデルタイプが指定されていません。',
            'attachable_type.in' => '指定された関連モデルタイプは無効です。',
            'attachable_id.required' => '関連モデルIDが指定されていません。',
            'attachable_id.integer' => '関連モデルIDは整数である必要があります。',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'files' => 'アップロードファイル',
            'files.*' => 'ファイル',
            'attachable_type' => '関連モデルタイプ',
            'attachable_id' => '関連モデルID',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // カスタムバリデーション：総ファイルサイズチェック
            if ($this->hasFile('files')) {
                $totalSize = 0;
                foreach ($this->file('files') as $file) {
                    $totalSize += $file->getSize();
                }
                
                // 総サイズ制限（100MB）
                $maxTotalSize = 100 * 1024 * 1024;
                if ($totalSize > $maxTotalSize) {
                    $validator->errors()->add('files', 'アップロードファイルの合計サイズが100MBを超えています。');
                }
            }

            // 関連モデル存在チェック（マルチテナント考慮）
            if ($this->filled('attachable_type') && $this->filled('attachable_id')) {
                $this->validateAttachableModel($validator);
            }
        });
    }

    /**
     * 関連モデルの存在確認とテナント境界チェック
     */
    private function validateAttachableModel($validator): void
    {
        $type = $this->input('attachable_type');
        $id = $this->input('attachable_id');
        $currentUser = auth()->user();

        try {
            switch ($type) {
                case 'App\Models\Post':
                    $model = \App\Models\Post::where('id', $id)
                        ->where('tenant_id', $currentUser->tenant_id)
                        ->first();
                    break;

                case 'App\Models\Comment':
                    $model = \App\Models\Comment::where('id', $id)
                        ->where('tenant_id', $currentUser->tenant_id)
                        ->first();
                    break;

                case 'App\Models\User':
                    $model = \App\Models\User::where('id', $id)
                        ->where('tenant_id', $currentUser->tenant_id)
                        ->first();
                    break;

                default:
                    $model = null;
            }

            if (!$model) {
                $validator->errors()->add('attachable_id', '指定された関連データが見つからないか、アクセス権限がありません。');
            }
        } catch (\Exception $e) {
            $validator->errors()->add('attachable_type', '関連モデルの検証中にエラーが発生しました。');
        }
    }
}
