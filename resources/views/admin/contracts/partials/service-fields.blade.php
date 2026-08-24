@php
    $selectedRoomId = (string) ($selectedRoomId ?? old('room_id'));
    $selectedServiceEnabled = $selectedServiceEnabled ?? old('service_enabled');
    $selectedParkingEnabled = (bool) ($selectedParkingEnabled ?? old('parking_enabled', false));
    $selectedParkingVehicleType = $selectedParkingVehicleType ?? old('parking_vehicle_type', \App\Models\Contract::PARKING_MOTORCYCLE);
    $selectedParkingQuantity = $selectedParkingQuantity ?? old('parking_quantity', 0);
    $conditionLabels = ['normal' => 'Sử dụng bình thường', 'damaged' => 'Có hư hỏng'];
@endphp

<div class="space-y-4 md:col-span-2" data-contract-services data-motorcycle-parking-fee="0">
    <section class="rounded-lg border border-slate-200 p-4">
        <h4 class="font-semibold text-slate-950">Dịch vụ đăng ký tính phí</h4>
        <p class="mt-1 text-sm text-slate-500">Chỉ các khoản dưới đây mới được cộng vào hóa đơn hàng tháng.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                <input type="checkbox" name="service_enabled" value="1" @checked($selectedServiceEnabled) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span><strong class="block text-slate-900">Dịch vụ chung</strong><span class="mt-1 block text-xs text-slate-500">{{ number_format((float) $setting->service_fee, 0, ',', '.') }}đ/tháng</span></span>
            </label>
            <div class="rounded-lg border border-slate-200 p-3 sm:col-span-2">
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="parking_enabled" value="1" data-parking-enabled @checked($selectedParkingEnabled) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span><strong class="block text-slate-900">Đăng ký xe máy miễn phí</strong><span class="mt-1 block text-xs text-slate-500">Mỗi người thuê được đăng ký tối đa một xe máy. Không tiếp nhận ô tô.</span></span>
                </label>
                <div data-parking-fields class="mt-4 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2 {{ $selectedParkingEnabled ? '' : 'hidden' }}">
                    <div>
                        <label for="parking_vehicle_type" class="mb-1 block text-sm font-semibold text-slate-700">Loại xe *</label>
                        <select id="parking_vehicle_type" name="parking_vehicle_type" data-parking-vehicle-type @disabled(! $selectedParkingEnabled) class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                            <option value="{{ \App\Models\Contract::PARKING_MOTORCYCLE }}">Xe máy — miễn phí</option>
                        </select>
                        @error('parking_vehicle_type') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="parking_quantity" class="mb-1 block text-sm font-semibold text-slate-700">Số lượng *</label>
                        <input id="parking_quantity" type="number" min="1" max="20" name="parking_quantity" value="{{ $selectedParkingQuantity > 0 ? $selectedParkingQuantity : 1 }}" data-parking-quantity @disabled(! $selectedParkingEnabled) class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        @error('parking_quantity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <p data-parking-estimate class="text-xs font-semibold text-indigo-700 sm:col-span-2"></p>
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200">
        <div class="border-b border-slate-200 px-4 py-3">
            <h4 class="font-semibold text-slate-950">Tài sản bàn giao của phòng</h4>
            <p class="mt-1 text-sm text-slate-500">Bàn, ghế, giường, tủ và thiết bị được lấy từ phòng đã chọn; danh sách sẽ được chốt khi gửi hợp đồng cho khách ký.</p>
        </div>
        <div class="p-4">
            <p data-room-inventory-prompt class="text-sm text-slate-500 {{ $selectedRoomId !== '' ? 'hidden' : '' }}">Chọn phòng để xem tài sản bàn giao.</p>
            @foreach($rooms as $room)
                <div data-room-inventory="{{ $room->id }}" class="{{ $selectedRoomId === (string) $room->id ? '' : 'hidden' }}">
                    @forelse($room->amenities as $asset)
                        <div class="flex flex-col gap-1 border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div><p class="text-sm font-semibold text-slate-900">{{ $asset->name }} × {{ $asset->pivot->quantity }}</p>@if($asset->description)<p class="mt-0.5 text-xs text-slate-500">{{ $asset->description }}</p>@endif</div>
                            <span class="text-xs font-semibold {{ $asset->pivot->condition === 'damaged' ? 'text-rose-700' : 'text-emerald-700' }}">{{ $conditionLabels[$asset->pivot->condition] ?? 'Chưa xác định' }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-amber-700">Phòng này chưa được khai báo tài sản bàn giao.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </section>
</div>
