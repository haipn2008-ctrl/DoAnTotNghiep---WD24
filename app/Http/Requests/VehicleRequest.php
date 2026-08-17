<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id;

        return [
            'tenant_id' => [
                'required',
                'exists:tenants,id',
            ],

            'vehicle_type' => [
                'required',
                'string',
                'max:100',
            ],

            'vehicle_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'license_plate' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'license_plate')
                    ->ignore($vehicleId),
            ],

            'color' => [
                'nullable',
                'string',
                'max:100',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Vui lòng chọn khách thuê.',
            'tenant_id.exists' => 'Khách thuê không tồn tại.',

            'vehicle_type.required' => 'Vui lòng chọn loại xe.',
            'vehicle_type.max' => 'Loại xe không được vượt quá 100 ký tự.',

            'vehicle_name.max' => 'Tên xe không được vượt quá 255 ký tự.',

            'license_plate.required' => 'Vui lòng nhập biển số xe.',
            'license_plate.unique' => 'Biển số xe đã tồn tại.',
            'license_plate.max' => 'Biển số xe không được vượt quá 50 ký tự.',

            'color.max' => 'Màu xe không được vượt quá 100 ký tự.',

            'note.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ];
    }
}
