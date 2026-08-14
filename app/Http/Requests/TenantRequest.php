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

            /*
            |--------------------------------------------------------------------------
            | Tài khoản
            |--------------------------------------------------------------------------
            */

            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('tenants', 'user_id')
                    ->ignore($tenantId),
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
                'nullable',
                'date',
                'before:today',
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

            /*
            |--------------------------------------------------------------------------
            | Xe
            |--------------------------------------------------------------------------
            */

            'vehicles' => [
                'nullable',
                'array',
            ],

            'vehicles.*.vehicle_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'vehicles.*.vehicle_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'vehicles.*.license_plate' => [
                'nullable',
                'string',
                'max:50',
                'distinct',
                'unique:vehicles,license_plate',
            ],

            'vehicles.*.color' => [
                'nullable',
                'string',
                'max:100',
            ],

            'vehicles.*.note' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation bổ sung
    |--------------------------------------------------------------------------
    */

    public function after(): array
    {
        return [
            function (Validator $validator): void {

                /*
                |--------------------------------------------------------------------------
                | Nếu user_id đã lỗi thì không kiểm tra tiếp
                |--------------------------------------------------------------------------
                */

                if ($validator->errors()->has('user_id')) {
                    return;
                }

                $user = User::with('role')
                    ->find($this->input('user_id'));

                $currentUserId = $this->route('tenant')?->user_id;

                /*
                |--------------------------------------------------------------------------
                | Kiểm tra tài khoản có đúng role khách thuê
                |--------------------------------------------------------------------------
                |
                | Controller đang cho phép:
                | - User
                | - Client
                |
                */

                $roleName = $user?->role?->role_name;

                if (! in_array($roleName, ['User', 'Client'], true)) {

                    $validator->errors()->add(
                        'user_id',
                        'Chỉ có thể liên kết tài khoản khách thuê.'
                    );

                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Kiểm tra trạng thái tài khoản
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $user->id !== (int) $currentUserId
                    && ! in_array(
                        $user->status,
                        [
                            User::STATUS_PENDING,
                            User::STATUS_ACTIVE,
                        ],
                        true
                    )
                ) {
                    $validator->errors()->add(
                        'user_id',
                        'Tài khoản khách thuê không ở trạng thái có thể liên kết.'
                    );
                }
            },
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | User
            |--------------------------------------------------------------------------
            */

            'user_id.required' =>
                'Vui lòng chọn tài khoản đăng nhập.',

            'user_id.exists' =>
                'Tài khoản không tồn tại.',

            'user_id.unique' =>
                'Tài khoản đã được liên kết với khách thuê khác.',

            /*
            |--------------------------------------------------------------------------
            | Thông tin cơ bản
            |--------------------------------------------------------------------------
            */

            'full_name.required' =>
                'Vui lòng nhập họ và tên.',

            'full_name.max' =>
                'Họ và tên không được vượt quá 255 ký tự.',

            'date_of_birth.date' =>
                'Ngày sinh không hợp lệ.',

            'date_of_birth.before' =>
                'Ngày sinh phải nhỏ hơn ngày hiện tại.',

            'gender.in' =>
                'Giới tính không hợp lệ.',

            'phone.required' =>
                'Vui lòng nhập số điện thoại.',

            'phone.regex' =>
                'Số điện thoại phải gồm từ 10 đến 15 chữ số.',

            'phone.unique' =>
                'Số điện thoại đã tồn tại.',

            'email.email' =>
                'Email không đúng định dạng.',

            'email.unique' =>
                'Email đã tồn tại.',

            /*
            |--------------------------------------------------------------------------
            | CCCD
            |--------------------------------------------------------------------------
            */

            'cccd.required' =>
                'Vui lòng nhập CCCD.',

            'cccd.digits' =>
                'CCCD phải gồm 12 chữ số.',

            'cccd.unique' =>
                'CCCD đã tồn tại.',

            'cccd_issue_date.date' =>
                'Ngày cấp CCCD không hợp lệ.',

            'cccd_issue_date.before_or_equal' =>
                'Ngày cấp CCCD không được lớn hơn ngày hiện tại.',

            'cccd_issue_date.after' =>
                'Ngày cấp CCCD phải sau ngày sinh.',

            /*
            |--------------------------------------------------------------------------
            | Xe
            |--------------------------------------------------------------------------
            */

            'vehicles.array' =>
                'Danh sách xe không hợp lệ.',

            'vehicles.*.vehicle_type.max' =>
                'Loại xe không được vượt quá 100 ký tự.',

            'vehicles.*.vehicle_name.max' =>
                'Tên xe không được vượt quá 255 ký tự.',

            'vehicles.*.license_plate.max' =>
                'Biển số xe không được vượt quá 50 ký tự.',

            'vehicles.*.license_plate.distinct' =>
                'Không được nhập trùng biển số xe.',

            'vehicles.*.license_plate.unique' =>
                'Biển số xe đã tồn tại trong hệ thống.',

            'vehicles.*.color.max' =>
                'Màu xe không được vượt quá 100 ký tự.',

            'vehicles.*.note.max' =>
                'Ghi chú xe không được vượt quá 500 ký tự.',
        ];
    }
}
