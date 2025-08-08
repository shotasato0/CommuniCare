<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('tenant_id', Auth::user()->tenant_id);
                })->ignore($this->user()->id)
            ],
            'tel' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'unit_id' => ['nullable', 'exists:units,id'],
        ];
    }
}
