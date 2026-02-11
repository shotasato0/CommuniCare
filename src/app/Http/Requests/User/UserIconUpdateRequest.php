<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ユーザーアイコン更新リクエスト
 */
class UserIconUpdateRequest extends FormRequest
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
            'icon' => 'required|image:allow_svg|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
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
            'icon.required' => '画像を選択してください。',
            'icon.image' => '画像ファイルを選択してください。',
            'icon.mimes' => '対応していない画像形式です。',
            'icon.max' => '画像のサイズが大きすぎます。4MB以下にしてください。',
        ];
    }
}
