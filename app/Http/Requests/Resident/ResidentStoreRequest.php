<?php

namespace App\Http\Requests\Resident;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 利用者登録リクエスト
 */
class ResidentStoreRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'unit_id' => 'required|exists:units,id',
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
            'name.required' => '利用者名は必須です。',
            'name.string' => '利用者名は文字列で入力してください。',
            'name.max' => '利用者名は255文字以内で入力してください。',
            'unit_id.required' => '所属部署は必須です。',
            'unit_id.exists' => '選択された部署は存在しません。',
        ];
    }
}