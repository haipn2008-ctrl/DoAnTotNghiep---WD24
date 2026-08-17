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
                'nullable',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $vehicles = $this->input('vehicles', []);

            if (! is_array($vehicles)) {
                return;
            }

            foreach ($vehicles as $index => $vehicle) {
                if (! is_array($vehicle)) {
                    continue;
                }

                $hasAnyValue =
                    filled($vehicle['vehicle_type'] ?? null)
                    || filled($vehicle['vehicle_name'] ?? null)
                    || filled($vehicle['license_plate'] ?? null)
                    || filled($vehicle['color'] ?? null)
                    || filled($vehicle['note'] ?? null);

                if (
                    $hasAnyValue
                    && blank($vehicle['license_plate'] ?? null)
                ) {
                    $validator->errors()->add(
                        "vehicles.$index.license_plate",
                        'Vui lòng nhập biển số xe.'
                    );
                }
            }
        });
    }
}
