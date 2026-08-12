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

        <form action="{{ route('admin.contracts.store') }}" method="POST" enctype="multipart/form-data" data-contract-schedule data-ajax-validation-form novalidate class="rounded-lg border border-slate-200 bg-white shadow-sm">
            @csrf

            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-950">Thông tin hợp đồng</h3>
                <p class="text-sm text-slate-500">Bước này chỉ lưu bản nháp; chưa ký, chưa thu cọc và chưa chiếm phòng.</p>
            </div>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div>
                    <label for="room_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Phòng thuê</label>
                    <select id="room_id" name="room_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Chọn phòng</option>
                        @foreach ($rooms as $room)
                            @php
                                $currentOccupancy = $room->activeContract;
                                $availabilityBlocked = $room->status === \App\Models\Room::STATUS_OCCUPIED
                                    && (! $currentOccupancy || $currentOccupancy->status === \App\Models\Contract::STATUS_EXPIRED || $currentOccupancy->end_date->copy()->endOfDay()->isPast());
                                $availableFrom = $room->status === \App\Models\Room::STATUS_OCCUPIED && ! $availabilityBlocked
                                    ? $currentOccupancy?->end_date?->copy()->addDay()
                                    : null;
                            @endphp
                            <option value="{{ $room->id }}" data-max-people="{{ $room->max_people }}" data-room-code="{{ $room->room_code }}" data-occupied-until="{{ $currentOccupancy?->end_date?->toDateString() }}" data-available-from="{{ $availableFrom?->toDateString() }}" data-availability-blocked="{{ $availabilityBlocked ? '1' : '0' }}" @selected((string) old('room_id') === (string) $room->id)>
                                {{ $room->room_code }} - {{ number_format($room->price, 0, ',', '.') }}đ/tháng{{ $availabilityBlocked ? ' (khách chưa trả phòng, chưa thể xếp lịch)' : ($availableFrom ? ' (có thể thuê từ '.$availableFrom->format('d/m/Y').')' : '') }}
                            </option>
                        @endforeach
                    </select>
                    <p data-room-availability-message class="mt-2 hidden rounded-lg border px-3 py-2 text-sm font-semibold"></p>
                    @error('room_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tenant_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Người đại diện thuê</label>
                    <select id="tenant_id" name="tenant_id" data-contract-representative class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Chọn người đại diện</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" data-full-name="{{ $tenant->full_name }}" data-phone="{{ $tenant->phone }}" data-date-of-birth="{{ $tenant->date_of_birth?->toDateString() }}" data-gender="{{ $tenant->gender }}" data-cccd="{{ $tenant->cccd }}" data-address="{{ $tenant->address }}" @selected((string) old('tenant_id') === (string) $tenant->id)>
                                {{ $tenant->full_name }} — {{ $tenant->user?->email }}{{ $tenant->cccd ? '' : ' (cần bổ sung CCCD)' }}
                            </option>
                        @endforeach
                    </select>
                    @error('tenant_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                @include('admin.contracts.partials.representative-fields')
                @include('admin.contracts.partials.member-selector')

                <div class="md:col-span-2 border-t border-slate-200 pt-5">
                    <h4 class="font-semibold text-slate-950">Thời hạn hợp đồng</h4>
                </div>

                <div>
                    <label for="start_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày bắt đầu thời hạn thuê *</label>
                    <input id="start_date" data-contract-start type="date" name="start_date" value="{{ old('start_date') }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('start_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contract_duration" class="mb-1.5 block text-sm font-semibold text-slate-700">Thời hạn *</label>
                    <select id="contract_duration" data-contract-duration name="contract_duration" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                        <option value="">Chọn thời hạn</option>
                        <option value="short_term" @selected(old('contract_duration') === 'short_term')>Thuê ít ngày</option>
                        <option value="3" @selected((string) old('contract_duration') === '3')>3 tháng</option>
                        <option value="6" @selected((string) old('contract_duration') === '6')>6 tháng</option>
                        <option value="12" @selected((string) old('contract_duration') === '12')>1 năm</option>
                    </select>
                    @error('contract_duration') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="end_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày kết thúc</label>
                    <input id="end_date" data-contract-end type="date" name="end_date" value="{{ old('end_date') }}" readonly required class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600">
                    @error('end_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 border-t border-slate-200 pt-5">
                    <h4 class="font-semibold text-slate-950">Ký, đặt cọc và nhận phòng</h4>
                </div>

                <div>
                    <label for="scheduled_move_in_date" class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày dự kiến nhận phòng *</label>
                    <input id="scheduled_move_in_date" data-contract-move-in type="date" name="scheduled_move_in_date" value="{{ old('scheduled_move_in_date') }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                    @error('scheduled_move_in_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="reservation_expires_at" class="mb-1.5 block text-sm font-semibold text-slate-700">Hạn cuối phải nhận phòng *</label>
                    <input id="reservation_expires_at" data-contract-move-in-deadline type="date" name="reservation_expires_at" value="{{ old('reservation_expires_at') }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">
                    <p data-contract-deadline-error class="mt-1 hidden text-sm font-semibold text-rose-600"></p>
                    @error('reservation_expires_at') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div data-contract-move-in-warning class="hidden md:col-span-2 rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">Hạn cuối chuyển vào quá dài so với thời gian thuê, bạn có chắc chắn không?</div>

                <div data-move-in-terms-confirmation class="hidden md:col-span-2">
                    <label class="flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm font-semibold text-amber-900">
                        <input data-move-in-terms-confirmed type="checkbox" name="move_in_terms_confirmed" value="1" @checked(old('move_in_terms_confirmed')) class="mt-0.5">
                        <span>Tôi xác nhận đã trao đổi và thống nhất với khách về ngày dự kiến nhận phòng và hạn cuối nhận phòng.</span>
                    </label>
                    @error('move_in_terms_confirmed') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="deposit_amount" class="mb-1.5 block text-sm font-semibold text-slate-700">Tiền cọc (VNĐ)</label>
                    <input id="deposit_amount" type="number" min="0" name="deposit_amount" value="{{ old('deposit_amount', 0) }}" placeholder="Nhập 0 nếu không thu cọc" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                    @error('deposit_amount') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                @include('admin.contracts.partials.service-fields', [
                    'selectedRoomId' => old('room_id'),
                    'selectedServiceEnabled' => old('service_enabled'),
                    'selectedParkingEnabled' => old('parking_enabled'),
                    'selectedParkingVehicleType' => old('parking_vehicle_type', \App\Models\Contract::PARKING_MOTORCYCLE),
                    'selectedParkingQuantity' => old('parking_quantity', 0),
                ])
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-save text-lg"></i>
                    Lưu bản nháp
                </button>
            </div>
        </form>
    </div>

@endsection
