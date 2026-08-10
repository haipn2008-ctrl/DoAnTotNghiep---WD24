<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
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
                'exists:users,id',
                Rule::unique('tenants', 'user_id')->ignore($tenantId),
            ],

            'full_name' => 'required|max:255',

            'date_of_birth' => 'nullable|date|before:today',

            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],

            'cccd' => [
                'required',
                'digits:12',
                Rule::unique('tenants')->ignore($tenantId),
            ],

            'cccd_issue_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
                Rule::when($this->filled('date_of_birth'), 'after:date_of_birth'),
            ],

            'cccd_issue_place' => 'nullable|max:255',

            'phone' => [
                'required',
                'regex:/^[0-9]{10,15}$/',
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

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('user_id')) {
                return;
            }

            $user = User::with('role')->find($this->input('user_id'));
            $currentUserId = $this->route('tenant')?->user_id;

            if (! $user?->isClient()) {
                $validator->errors()->add('user_id', 'Chỉ có thể liên kết tài khoản khách thuê.');

                return;
            }

            if ((int) $user->id !== (int) $currentUserId
                && ! in_array($user->status, [User::STATUS_PENDING, User::STATUS_ACTIVE], true)) {
                $validator->errors()->add('user_id', 'Tài khoản khách thuê không ở trạng thái có thể liên kết.');
            }
        }];
    }

    #[Override]
    public function messages()
    {
        return [
            'user_id.required' => 'Vui lòng chọn tài khoản đăng nhập.',
            'user_id.exists' => 'Tài khoản không tồn tại.',
            'user_id.unique' => 'Tài khoản đã được liên kết với khách thuê khác.',
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
