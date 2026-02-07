<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * スケジュール更新リクエスト
 */
class ScheduleUpdateRequest extends FormRequest
{
    /**
     * 認可
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = Auth::user()->tenant_id;
        
        return [
            'date' => 'required|date|date_format:Y-m-d',
            'resident_id' => [
                'required',
                'exists:residents,id',
                Rule::exists('residents', 'id')->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                }),
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'memo' => 'nullable|string|max:1000',
        ];
    }

    /**
     * エラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => '日付は必須です。',
            'date.date' => '日付の形式が正しくありません。',
            'date.date_format' => '日付はYYYY-MM-DD形式で入力してください。',
            'resident_id.required' => '利用者の指定は必須です。',
            'resident_id.exists' => '指定された利用者は存在しないか、アクセスできません。',
            'start_time.required' => '開始時刻は必須です。',
            'start_time.date_format' => '開始時刻はHH:MM形式で入力してください。',
            'end_time.required' => '終了時刻は必須です。',
            'end_time.date_format' => '終了時刻はHH:MM形式で入力してください。',
            'end_time.after' => '終了時刻は開始時刻より後である必要があります。',
            'memo.string' => 'メモは文字列で入力してください。',
            'memo.max' => 'メモは1000文字以内で入力してください。',
        ];
    }
}
