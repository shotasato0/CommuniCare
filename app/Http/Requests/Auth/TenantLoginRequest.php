<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantLoginRequest extends FormRequest
{
    public function authorize()
    {
        // ここをtrueにすることで、全てのユーザーがこのリクエストを使用できる
        return true;
    }

    public function rules()
    {
        return [
            'business_name' => 'required|string|max:255',
            'tenant_domain_id' => 'required|string|max:255',
        ];
    }
}

