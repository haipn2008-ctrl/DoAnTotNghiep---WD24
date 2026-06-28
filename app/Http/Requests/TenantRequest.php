<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id;

        return [
            'user_id' => [
                'required',
                'exists:users,id'
            ],
            'full_name' => 'required|max:255',

            'date_of_birth' => 'nullable|date',

            'cccd' => [
                'required',
                'digits:12',
                Rule::unique('tenants')->ignore($tenantId),
            ],

            'cccd_issue_date' => 'nullable|date',

            'cccd_issue_place' => 'nullable|max:255',

            'phone' => [
                'required',
                Rule::unique('tenants')->ignore($tenantId),
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('tenants')->ignore($tenantId),
            ],

            'address' => 'nullable|max:500',
        ];
    }
    #[Override]
    public function messages()
    {
        return [
            'user_id.required' => 'Vui lòng chọn tài khoản đăng nhập.',
            'user_id.exists' => 'Tài khoản không tồn tại.',
        ];
    }
}
