<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理者権限移譲リクエスト
 */
class AdminTransferRequest extends FormRequest
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
            'new_admin_id' => 'required|exists:users,id',
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
            'new_admin_id.required' => '新しい管理者の選択は必須です。',
            'new_admin_id.exists' => '指定されたユーザーは存在しません。',
        ];
    }
}