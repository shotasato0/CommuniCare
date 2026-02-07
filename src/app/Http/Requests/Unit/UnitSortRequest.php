<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 部署並び替えリクエスト
 */
class UnitSortRequest extends FormRequest
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
            'units' => 'required|array',
            'units.*.id' => 'required|integer|exists:units,id',
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
            'units.required' => '部署の並び順データが必要です。',
            'units.array' => '部署データは配列形式で送信してください。',
            'units.*.id.required' => '部署IDは必須です。',
            'units.*.id.integer' => '部署IDは整数で指定してください。',
            'units.*.id.exists' => '指定された部署IDは存在しません。',
        ];
    }
}