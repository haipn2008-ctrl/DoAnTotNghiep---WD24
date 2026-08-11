<?php

namespace App\Http\Requests;

use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Room|null $room */
        $room = $this->route('room');
        $creating = $room === null;

        return [
            'room_code' => ['required', 'string', 'max:50', Rule::unique('rooms', 'room_code')->ignore($room?->id)],
            'floor' => ['required', 'integer', 'between:1,5'],
            'price' => ['required', 'numeric', 'min:0'],
            'area' => ['required', 'numeric', 'min:1'],
            'max_people' => ['required', 'integer', 'between:1,20'],
            // Hai trường vận hành này không bao giờ được phép chỉnh trực tiếp khi tạo phòng.
            'current_people' => ['prohibited'],
            'status' => $creating
                ? ['prohibited']
                : ['required', Rule::in([Room::STATUS_AVAILABLE, Room::STATUS_OCCUPIED, Room::STATUS_MAINTENANCE])],
            'description' => ['nullable', 'string'],
            // Giữ tương thích request cũ; ảnh này cũng được lưu như một bằng chứng mới, không xóa ảnh trước.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'images' => ['nullable', 'array', 'max:15'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['distinct', Rule::exists('amenities', 'id')->where('is_active', true)],
            'inventory' => ['nullable', 'array'],
            'inventory.*' => ['array'],
            'inventory.*.selected' => ['nullable', 'boolean'],
            'inventory.*.quantity' => ['nullable', 'integer', 'between:1,100'],
            'inventory.*.condition' => ['nullable', Rule::in(['normal', 'damaged'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Room|null $room */
            $room = $this->route('room');

            foreach (array_keys((array) $this->input('inventory', [])) as $amenityId) {
                if (! ctype_digit((string) $amenityId) || ! Amenity::query()->active()->whereKey($amenityId)->exists()) {
                    $validator->errors()->add("inventory.{$amenityId}", 'Tiện ích hoặc tài sản đã chọn không tồn tại.');
                }
            }

            if (! $room) {
                return;
            }

            $hasOccupancy = $room->activeContract()->exists();
            $requestedStatus = $this->input('status');

            if ($hasOccupancy && $requestedStatus !== Room::STATUS_OCCUPIED) {
                $validator->errors()->add('status', 'Phòng đang có khách chỉ được đổi trạng thái bằng quy trình checkout hợp đồng.');
            }

            if (! $hasOccupancy && $requestedStatus === Room::STATUS_OCCUPIED) {
                $validator->errors()->add('status', 'Chỉ quy trình check-in hợp đồng mới được chuyển phòng sang Đang thuê.');
            }

            if ((int) $this->input('max_people') < (int) $room->current_people) {
                $validator->errors()->add('max_people', 'Sức chứa tối đa không được nhỏ hơn số người đang ở hiện tại.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'room_code.required' => 'Mã phòng không được bỏ trống.',
            'room_code.max' => 'Mã phòng không được vượt quá 50 ký tự.',
            'room_code.unique' => 'Mã phòng đã tồn tại.',
            'floor.required' => 'Vui lòng chọn tầng.',
            'floor.between' => 'Tầng phải từ 1 đến 5.',
            'price.required' => 'Giá thuê không được bỏ trống.',
            'price.numeric' => 'Giá thuê phải là số.',
            'price.min' => 'Giá thuê không được nhỏ hơn 0.',
            'area.required' => 'Diện tích không được bỏ trống.',
            'area.numeric' => 'Diện tích phải là số.',
            'area.min' => 'Diện tích phải lớn hơn 0.',
            'max_people.required' => 'Vui lòng nhập sức chứa tối đa.',
            'max_people.between' => 'Sức chứa phải từ 1 đến 20 người.',
            'current_people.prohibited' => 'Số người hiện tại do quy trình check-in/checkout tự cập nhật, không được nhập tay.',
            'status.prohibited' => 'Phòng mới luôn ở trạng thái Trống, không được gán trạng thái bằng request.',
            'status.in' => 'Trạng thái phòng không hợp lệ.',
            'image.image' => 'File phải là ảnh.',
            'image.mimes' => 'Ảnh chỉ hỗ trợ JPG, JPEG, PNG hoặc WebP.',
            'image.max' => 'Mỗi ảnh tối đa 8 MB.',
            'images.max' => 'Mỗi lần chỉ được tải tối đa 15 ảnh.',
            'images.*.image' => 'Mỗi file tải lên phải là ảnh.',
            'images.*.mimes' => 'Ảnh chỉ hỗ trợ JPG, JPEG, PNG hoặc WebP.',
            'images.*.max' => 'Mỗi ảnh tối đa 8 MB.',
            'inventory.*.quantity.between' => 'Số lượng tài sản phải từ 1 đến 100.',
            'inventory.*.condition.in' => 'Tình trạng tài sản không hợp lệ.',
        ];
    }
}
