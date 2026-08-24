<?php

namespace App\Http\Requests;

use App\Rules\AdultDateOfBirth;
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
            /*
            |--------------------------------------------------------------------------
            | Tài khoản
            |--------------------------------------------------------------------------
            */

            'user_id' => [
                'prohibited',
            ],

            /*
            |--------------------------------------------------------------------------
            | Thông tin cơ bản
            |--------------------------------------------------------------------------
            */

            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'date_of_birth' => [
                'required',
                'date',
                new AdultDateOfBirth,
            ],

            'gender' => [
                'nullable',
                Rule::in([
                    'male',
                    'female',
                    'other',
                ]),
            ],

            'phone' => [
                'required',
                'regex:/^[0-9]{10,15}$/',
                Rule::unique('tenants', 'phone')
                    ->ignore($tenantId),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('tenants', 'email')
                    ->ignore($tenantId),
            ],

            'address' => [
                'nullable',
                'string',
                'max:500',
            ],

            /*
            |--------------------------------------------------------------------------
            | CCCD
            |--------------------------------------------------------------------------
            */

            'cccd' => [
                'required',
                'digits:12',
                Rule::unique('tenants', 'cccd')
                    ->ignore($tenantId),
            ],

            'cccd_issue_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
                Rule::when(
                    $this->filled('date_of_birth'),
                    'after:date_of_birth'
                ),
            ],

            'cccd_issue_place' => [
                'nullable',
                'string',
                'max:255',
            ],

        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'user_id.prohibited' => 'Không thể thay đổi tài khoản liên kết từ hồ sơ khách thuê.',
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'cccd.required' => 'Vui lòng nhập CCCD.',
            'cccd.digits' => 'CCCD phải gồm 12 chữ số.',
            'cccd.unique' => 'CCCD đã tồn tại.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải gồm từ 10 đến 15 chữ số.',
            'phone.unique' => 'Số điện thoại đã tồn tại.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã tồn tại.',
        ];
    }
}
