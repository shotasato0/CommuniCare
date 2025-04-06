<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
     * バリデーションルールを定義
     * 
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'tel' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'unit_id' => 'nullable|exists:units,id',
        ];
    }

    /**
     * エラーメッセージを定義
     * 
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => '名前は必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'unit_id.exists' => '選択されたユニットは存在しません。',
        ];
    }
}
