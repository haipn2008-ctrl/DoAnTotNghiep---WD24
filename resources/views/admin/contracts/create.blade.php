@extends('layouts.admin.index')

@section('title', 'Tạo hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Tạo hợp đồng')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Quản lý hợp đồng</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Tạo hợp đồng thuê phòng</h2>
            </div>

            <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="bx bx-arrow-back text-lg"></i>
                Quay lại
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Vui lòng kiểm tra lại thông tin.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.contracts.store') }}" method="POST" class="rounded-lg border border-slate-200 bg-white shadow-sm">
            @csrf

            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Thông tin hợp đồng</h3>
                <p class="text-sm text-slate-500">Chọn phòng trống, khách thuê và thời hạn thuê.</p>
            </div>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div>
                    <label for="room_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Phòng thuê</label>
                    <select id="room_id" name="room_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Chọn phòng</option>
                        @foreach ($rooms as $room)
                            @php($suggestion = $handoverSuggestions->get($room->id))
                            <option value="{{ $room->id }}" data-electricity="{{ $suggestion['electricity'] ?? '' }}" data-water="{{ $suggestion['water'] ?? '' }}" @selected((string) old('room_id') === (string) $room->id)>
                                {{ $room->room_code }} - {{ number_format($room->price, 0, ',', '.') }}đ/tháng
                            </option>
                        @endforeach
                    </select>
                    @error('room_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tenant_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Người thuê</label>
                    <select id="tenant_id" name="tenant_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Chọn người thuê</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>
                                {{ $tenant->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('tenant_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="start_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày bắt đầu</label>
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('start_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="end_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày kết thúc</label>
                    <input id="end_date" type="date" name="end_date" value="{{ old('end_date') }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('end_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="deposit_amount" class="mb-1.5 block text-sm font-semibold text-slate-700">Tiền cọc (VNĐ)</label>
                    <input id="deposit_amount" type="number" min="0" name="deposit_amount" value="{{ old('deposit_amount') }}" placeholder="Nhập tiền cọc" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('deposit_amount') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="number_of_people" class="mb-1.5 block text-sm font-semibold text-slate-700">Số người ở</label>
                    <input id="number_of_people" type="number" min="1" max="20" name="number_of_people" value="{{ old('number_of_people', 1) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('number_of_people') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                    <h4 class="font-semibold text-indigo-950">Chỉ số bàn giao phòng</h4>
                    <p class="mt-1 text-sm text-indigo-700">Nhập chỉ số đồng hồ tại ngày bắt đầu hợp đồng; hệ thống không mặc định chỉ số đầu tiên bằng 0.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div><label for="handover_electricity" class="mb-1.5 block text-sm font-semibold">Chỉ số điện bàn giao (kWh)</label><input id="handover_electricity" type="number" min="0" name="handover_electricity" value="{{ old('handover_electricity') }}" required class="h-11 w-full rounded-lg border border-indigo-200 px-3"><p id="handoverElectricityHint" class="mt-1 text-xs text-indigo-700">Chọn phòng để lấy chỉ số gần nhất.</p>@error('handover_electricity')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label for="handover_water" class="mb-1.5 block text-sm font-semibold">Chỉ số nước bàn giao (m³)</label><input id="handover_water" type="number" min="0" name="handover_water" value="{{ old('handover_water') }}" required class="h-11 w-full rounded-lg border border-indigo-200 px-3"><p id="handoverWaterHint" class="mt-1 text-xs text-indigo-700">Chọn phòng để lấy chỉ số gần nhất.</p>@error('handover_water')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                </div>

                <div class="md:col-span-2 rounded-lg border border-slate-200 p-4">
                    <h4 class="font-semibold text-slate-950">Dịch vụ đăng ký</h4>
                    <p class="mt-1 text-sm text-slate-500">Chỉ dịch vụ được chọn mới được cộng vào hóa đơn.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="internet_enabled" value="1" @checked(old('internet_enabled'))> Internet</label>
                        <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="service_enabled" value="1" @checked(old('service_enabled'))> Dịch vụ chung</label>
                        <div><label for="parking_quantity" class="mb-1 block text-sm font-medium">Số xe đăng ký</label><input id="parking_quantity" type="number" min="0" max="20" name="parking_quantity" value="{{ old('parking_quantity', 0) }}" class="h-10 w-full rounded-lg border border-slate-200 px-3"></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-save text-lg"></i>
                    Tạo hợp đồng
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const room = document.getElementById('room_id');
                const electricity = document.getElementById('handover_electricity');
                const water = document.getElementById('handover_water');
                const electricityHint = document.getElementById('handoverElectricityHint');
                const waterHint = document.getElementById('handoverWaterHint');

                const applySuggestion = (overwrite) => {
                    const option = room.options[room.selectedIndex];
                    const suggestedElectricity = option?.dataset.electricity ?? '';
                    const suggestedWater = option?.dataset.water ?? '';

                    electricity.min = suggestedElectricity || '0';
                    water.min = suggestedWater || '0';
                    if (overwrite || electricity.value === '') electricity.value = suggestedElectricity;
                    if (overwrite || water.value === '') water.value = suggestedWater;

                    electricityHint.textContent = suggestedElectricity === ''
                        ? 'Phòng chưa có chỉ số trước đó; hãy nhập số trên đồng hồ.'
                        : `Gần nhất của phòng: ${suggestedElectricity} kWh`;
                    waterHint.textContent = suggestedWater === ''
                        ? 'Phòng chưa có chỉ số trước đó; hãy nhập số trên đồng hồ.'
                        : `Gần nhất của phòng: ${suggestedWater} m³`;
                };

                room.addEventListener('change', () => applySuggestion(true));
                applySuggestion(false);
            });
        </script>
    @endpush
@endsection
