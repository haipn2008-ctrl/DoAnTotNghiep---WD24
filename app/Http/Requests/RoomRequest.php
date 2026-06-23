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
                Rule::unique('rooms', 'room_code')->ignore($roomId)
            ],

            'floor' => [
                'required',
                'integer',
                'between:1,5'
            ],

            'room_type' => [
                'required',
                Rule::in([
                    'standard',
                    'vip'
                ])
            ],

            'price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'area' => [
                'required',
                'numeric',
                'min:1'
            ],

            'max_people' => [
                'required',
                'integer',
                'min:1',
                'max:10'
            ],
            'current_people' => [
                'required',
                'integer',
                'min:0'
            ],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'occupied',
                    'maintenance'
                ])
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],

            'description' => [
                'nullable',
                'string'
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'room_code.required' => 'Mã phòng không được bỏ trống',
            'room_code.unique' => 'Mã phòng đã tồn tại',

            'floor.required' => 'Vui lòng chọn tầng',
            'floor.between' => 'Tầng phải từ 1 đến 5',

            'room_type.required' => 'Vui lòng chọn loại phòng',

            'price.required' => 'Giá thuê không được bỏ trống',
            'price.numeric' => 'Giá thuê phải là số',

            'area.required' => 'Diện tích không được bỏ trống',
            'area.numeric' => 'Diện tích phải là số',

            'max_people.required' => 'Vui lòng nhập số người tối đa',
            'current_people.required' => 'Vui lòng nhập số người hiện tại',
            'current_people.integer' => 'Số người hiện tại phải là số nguyên',
            'current_people.min' => 'Số người hiện tại không được nhỏ hơn 0',

            'status.required' => 'Vui lòng chọn trạng thái',

            'image.image' => 'File phải là ảnh',
            'image.mimes' => 'Ảnh chỉ hỗ trợ jpg, jpeg, png, webp',
            'image.max' => 'Ảnh tối đa 2MB',
        ];
    }
}
