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
                Rule::unique('rooms', 'room_code')->ignore($roomId)
            ],

            'price' => 'required|numeric|min:0',
            'area' => 'required|numeric|min:1',
            'status' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'room_code.required' => 'Mã phòng không được bỏ trống',
            'room_code.unique' => 'Mã phòng đã tồn tại',

            'price.required' => 'Giá thuê không được bỏ trống',
            'price.numeric' => 'Giá thuê phải là số',

            'area.required' => 'Diện tích không được bỏ trống',
            'area.numeric' => 'Diện tích phải là số',
        ];
    }
}
