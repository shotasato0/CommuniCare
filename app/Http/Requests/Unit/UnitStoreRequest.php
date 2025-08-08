<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * 部署登録リクエスト
 */
class UnitStoreRequest extends FormRequest
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
        $tenantId = optional($this->user())->tenant_id;
        
        if (is_null($tenantId)) {
            throw ValidationException::withMessages([
                'tenant' => 'テナントコンテキストが初期化されていません。'
            ]);
        }
        
        return [
            'name' => 'required|string|max:255|unique:units,name,NULL,id,tenant_id,' . $tenantId,
            'description' => 'nullable|string',
            'visibility' => 'nullable|string|in:public,private',
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
            'name.required' => '部署名は必須です。',
            'name.string' => '部署名は文字列で入力してください。',
            'name.max' => '部署名は255文字以内で入力してください。',
            'name.unique' => 'この部署名は既に登録されています。',
            'visibility.in' => '公開設定は「public」または「private」を指定してください。',
        ];
    }
}