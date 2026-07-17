<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomId = $this->route('room')?->id;

        return [
            'room_code' => [
                'required',
                'max:50',
                Rule::unique('rooms', 'room_code')->ignore($roomId),
            ],

            'floor' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'area' => [
                'required',
                'numeric',
                'min:1',
            ],

            'current_people' => [
                'required',
                'integer',
                'between:0,4',
            ],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'occupied',
                    'maintenance',
                ]),
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'amenities' => [
                'nullable',
                'array',
            ],

            'amenities.*' => [
                'exists:amenities,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'room_code.required' => 'Mã phòng không được bỏ trống',
            'room_code.max' => 'Mã phòng không được vượt quá 50 ký tự',
            'room_code.unique' => 'Mã phòng đã tồn tại',

            'floor.required' => 'Vui lòng chọn tầng',
            'floor.integer' => 'Tầng phải là số nguyên',
            'floor.between' => 'Tầng phải từ 1 đến 5',

            'price.required' => 'Giá thuê không được bỏ trống',
            'price.numeric' => 'Giá thuê phải là số',
            'price.min' => 'Giá thuê không được nhỏ hơn 0',

            'area.required' => 'Diện tích không được bỏ trống',
            'area.numeric' => 'Diện tích phải là số',
            'area.min' => 'Diện tích phải lớn hơn 0',

            'current_people.required' => 'Vui lòng nhập số người hiện tại',
            'current_people.integer' => 'Số người hiện tại phải là số nguyên',
            'current_people.between' => 'Số người phải từ 0 đến 4',

            'status.required' => 'Vui lòng chọn trạng thái',
            'status.in' => 'Trạng thái phòng không hợp lệ',

            'image.image' => 'File phải là ảnh',
            'image.mimes' => 'Ảnh chỉ hỗ trợ jpg, jpeg, png, webp',
            'image.max' => 'Ảnh tối đa 2MB',

            'description.string' => 'Mô tả không hợp lệ',
            'amenities.array' => 'Tiện nghi không hợp lệ',
            'amenities.*.exists' => 'Tiện nghi đã chọn không tồn tại',
        ];
    }
}
