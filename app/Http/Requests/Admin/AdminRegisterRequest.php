<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 管理者登録リクエスト
 */
class AdminRegisterRequest extends FormRequest
{
    /**
     * 認可
     * テナントコンテキストが存在する場合のみ許可
     *
     * @return bool
     */
    public function authorize()
    {
        return tenant('id') !== null;
    }

    /**
     * バリデーションルール
     *
     * @return array
     */
    public function rules()
    {
        $tenantId = tenant('id');
        
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,NULL,id,tenant_id,' . $tenantId,
            'username_id' => 'required|string|max:255|unique:users,username_id,NULL,id,tenant_id,' . $tenantId,
            'password' => 'required|string|min:8|confirmed',
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
            'name.required' => '名前は必須です。',
            'name.string' => '名前は文字列で入力してください。',
            'name.max' => '名前は255文字以内で入力してください。',
            'email.required' => 'メールアドレスは必須です。',
            'email.string' => 'メールアドレスは文字列で入力してください。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'username_id.required' => 'ユーザー名は必須です。',
            'username_id.string' => 'ユーザー名は文字列で入力してください。',
            'username_id.max' => 'ユーザー名は255文字以内で入力してください。',
            'username_id.unique' => 'このユーザー名は既に使用されています。',
            'password.required' => 'パスワードは必須です。',
            'password.string' => 'パスワードは文字列で入力してください。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワード確認が一致していません。',
        ];
    }
}