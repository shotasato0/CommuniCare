<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Tenant;

class TenantLoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'tenant_domain_id' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // business_nameが正しいか確認
        $tenant = Tenant::whereJsonContains('data->business_name', $this->business_name)->first();

        if (!$tenant) {
            // attributesのbusiness_nameを取得してエラーメッセージに反映
            $validator->errors()->add(
                'business_name',
                trans('validation.custom.business_name.exists', ['attribute' => trans('validation.attributes.business_name')])
            );
        } else {
            // tenant_domain_idがそのテナントに対して正しいかを確認
            if ($tenant->tenant_domain_id !== $this->tenant_domain_id) {
                $validator->errors()->add(
                    'tenant_domain_id',
                    trans('validation.custom.tenant_domain_id.correct', ['attribute' => trans('validation.attributes.tenant_domain_id')])
                );
            }
        }
    });
}

}
