@php
    $isEdit = isset($tenant);

    $action = $isEdit
        ? route('admin.tenants.update', $tenant)
        : route('admin.tenants.store');

    $selectedUser = old(
        'user_id',
        $tenant->user_id ?? ''
    );

    /*
    |--------------------------------------------------------------------------
    | Danh sách xe
    |--------------------------------------------------------------------------
    */

    $oldVehicles = old('vehicles');

    if ($oldVehicles !== null) {
        $vehicles = $oldVehicles;
    } elseif ($isEdit) {
        $vehicles = $tenant->vehicles
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'vehicle_type' => $vehicle->vehicle_type,
                    'vehicle_name' => $vehicle->vehicle_name,
                    'license_plate' => $vehicle->license_plate,
                    'color' => $vehicle->color,
                    'note' => $vehicle->note,
                ];
            })
            ->toArray();
    } else {
        $vehicles = [];
    }
@endphp

@if ($errors->any())

    <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4">

        <div class="flex items-start gap-3">

            <i class="bx bx-error-circle text-xl text-rose-600"></i>

            <div>

                <h3 class="font-semibold text-rose-800">
                    Vui lòng kiểm tra lại thông tin
                </h3>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        </div>

    </div>

@endif

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

    {{-- ================================================================
    TÀI KHOẢN
    ================================================================= --}}

    <div class="border-b border-slate-200 px-5 py-4">

        <h3 class="font-semibold text-slate-950">
            Tài khoản khách thuê
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Liên kết khách thuê với tài khoản đăng nhập trên hệ thống.
        </p>

    </div>

    <div class="p-5">

        <label for="user_id" class="mb-1.5 block text-sm font-semibold text-slate-700">
            Tài khoản đăng nhập
            <span class="text-rose-500">*</span>
        </label>

        <select id="user_id" name="user_id"
            class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            <option value="">
                Chọn tài khoản
            </option>

            @foreach ($users as $user)

                <option value="{{ $user->id }}" @selected(
                    (string) $selectedUser ===
                    (string) $user->id
                )>
                    {{ $user->name }}
                    ({{ $user->email }})
                </option>

            @endforeach

        </select>

        @error('user_id')

            <p class="mt-1 text-sm text-rose-600">
                {{ $message }}
            </p>

        @enderror

    </div>


    {{-- ================================================================
    THÔNG TIN CƠ BẢN
    ================================================================= --}}

    <div class="border-t border-slate-200 px-5 py-4">

        <h3 class="font-semibold text-slate-950">
            1. Thông tin cơ bản
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Thông tin cá nhân của khách thuê.
        </p>

    </div>

    <div class="grid gap-5 p-5 md:grid-cols-2">

        {{-- Họ tên --}}

        <div>

            <label for="full_name" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Họ và tên
                <span class="text-rose-500">*</span>
            </label>

            <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $tenant->full_name ?? '') }}"
                placeholder="Nguyễn Văn A"
                class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            @error('full_name')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- Ngày sinh --}}

        <div>

            <label for="date_of_birth" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Ngày sinh
            </label>

            <input id="date_of_birth" type="date" name="date_of_birth"
                value="{{ old('date_of_birth', isset($tenant->date_of_birth) ? $tenant->date_of_birth->format('Y-m-d') : '') }}"
                class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            @error('date_of_birth')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- Giới tính --}}

        <div>

            <label for="gender" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Giới tính
            </label>

            <select id="gender" name="gender" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm">

                <option value="">
                    Chưa cập nhật
                </option>

                <option value="male" @selected(
                    old(
                        'gender',
                        $tenant->gender ?? ''
                    ) === 'male'
                )>
                    Nam
                </option>

                <option value="female" @selected(
                    old(
                        'gender',
                        $tenant->gender ?? ''
                    ) === 'female'
                )>
                    Nữ
                </option>

                <option value="other" @selected(
                    old(
                        'gender',
                        $tenant->gender ?? ''
                    ) === 'other'
                )>
                    Khác
                </option>

            </select>

            @error('gender')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- Số điện thoại --}}

        <div>

            <label for="phone" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Số điện thoại
                <span class="text-rose-500">*</span>
            </label>

            <input id="phone" type="text" name="phone" value="{{ old('phone', $tenant->phone ?? '') }}"
                placeholder="0366123456"
                class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            @error('phone')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- Email --}}

        <div>

            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Email
            </label>

            <input id="email" type="email" name="email" value="{{ old('email', $tenant->email ?? '') }}"
                placeholder="example@gmail.com"
                class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            @error('email')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- Địa chỉ --}}

        <div class="md:col-span-2">

            <label for="address" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Địa chỉ thường trú
            </label>

            <textarea id="address" name="address" rows="3" placeholder="Nhập địa chỉ thường trú"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">{{ old('address', $tenant->address ?? '') }}</textarea>

            @error('address')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>

    </div>


    {{-- ================================================================
    CCCD
    ================================================================= --}}

    <div class="border-t border-slate-200 px-5 py-4">

        <h3 class="font-semibold text-slate-950">
            2. Giấy tờ nhận diện
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Thông tin căn cước công dân của khách thuê.
        </p>

    </div>

    <div class="grid gap-5 p-5 md:grid-cols-2">

        {{-- CCCD --}}

        <div>

            <label for="cccd" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Số CCCD
                <span class="text-rose-500">*</span>
            </label>

            <input id="cccd" type="text" name="cccd" maxlength="12" value="{{ old('cccd', $tenant->cccd ?? '') }}"
                placeholder="012345678901"
                class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            @error('cccd')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- Ngày cấp --}}

        <div>

            <label for="cccd_issue_date" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Ngày cấp CCCD
            </label>

            <input id="cccd_issue_date" type="date" name="cccd_issue_date"
                value="{{ old('cccd_issue_date', isset($tenant->cccd_issue_date) ? $tenant->cccd_issue_date->format('Y-m-d') : '') }}"
                class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            @error('cccd_issue_date')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>


        {{-- Nơi cấp --}}

        <div class="md:col-span-2">

            <label for="cccd_issue_place" class="mb-1.5 block text-sm font-semibold text-slate-700">
                Nơi cấp CCCD
            </label>

            <input id="cccd_issue_place" type="text" name="cccd_issue_place"
                value="{{ old('cccd_issue_place', $tenant->cccd_issue_place ?? '') }}"
                placeholder="Cục Cảnh sát QLHC về TTXH"
                class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            @error('cccd_issue_place')

                <p class="mt-1 text-sm text-rose-600">
                    {{ $message }}
                </p>

            @enderror

        </div>

    </div>


    {{-- ================================================================
    XE
    ================================================================= --}}

    <div class="border-t border-slate-200 px-5 py-4">

        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">

            <div>

                <h3 class="font-semibold text-slate-950">
                    3. Thông tin phương tiện
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Khách thuê có thể đăng ký một hoặc nhiều phương tiện.
                </p>

            </div>

            <button type="button" id="add-vehicle"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                <i class="bx bx-plus text-lg"></i>
                Thêm xe
            </button>

        </div>

    </div>


    <div class="space-y-4 p-5">

        <div id="vehicles-container" class="space-y-4"></div>

        <div id="no-vehicle-message"
            class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-center text-sm text-slate-500">
            Chưa đăng ký phương tiện.
            Nếu khách thuê có xe, bấm
            <strong>Thêm xe</strong>.
        </div>

    </div>


    {{-- ================================================================
    NÚT
    ================================================================= --}}

    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">

        <a href="{{ route('admin.tenants.index') }}"
            class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Hủy
        </a>

        <button type="submit"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
            <i class="bx bx-save text-lg"></i>

            {{ $isEdit ? 'Cập nhật' : 'Thêm khách thuê' }}

        </button>

    </div>

</div>


{{-- ================================================================
TEMPLATE XE
================================================================= --}}

<template id="vehicle-template">

    <div class="vehicle-item rounded-xl border border-slate-200 bg-slate-50 p-4">

        <div class="mb-4 flex items-center justify-between">

            <h4 class="font-semibold text-slate-800">
                Xe
                <span class="vehicle-number"></span>
            </h4>

            <button type="button"
                class="remove-vehicle inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                <i class="bx bx-trash"></i>
                Xóa
            </button>

        </div>


        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">

            {{-- Loại xe --}}

            <div>

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Loại xe
                </label>

                <input type="text" data-field="vehicle_type" placeholder="Xe máy"
                    class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            </div>


            {{-- Tên xe --}}

            <div>

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Tên xe
                </label>

                <input type="text" data-field="vehicle_name" placeholder="Honda Vision"
                    class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            </div>


            {{-- Biển số --}}

            <div>

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Biển số xe
                </label>

                <input type="text" data-field="license_plate" placeholder="29A1-123.45"
                    class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            </div>


            {{-- Màu --}}

            <div>

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Màu xe
                </label>

                <input type="text" data-field="color" placeholder="Đen"
                    class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">

            </div>


            {{-- Ghi chú --}}

            <div class="md:col-span-2 lg:col-span-4">

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Ghi chú
                </label>

                <textarea data-field="note" rows="2" placeholder="Ghi chú về phương tiện..."
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"></textarea>

            </div>

        </div>

    </div>

</template>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        const container = document.getElementById('vehicles-container');
        const template = document.getElementById('vehicle-template');
        const addButton = document.getElementById('add-vehicle');
        const emptyMessage = document.getElementById('no-vehicle-message');

        let vehicleIndex = 0;

        function updateEmptyMessage() {

            const count =
                container.querySelectorAll('.vehicle-item').length;

            emptyMessage.classList.toggle(
                'hidden',
                count > 0
            );
        }

        function updateVehicleNumbers() {

            const vehicles =
                container.querySelectorAll('.vehicle-item');

            vehicles.forEach(function (vehicle, index) {

                const number =
                    vehicle.querySelector('.vehicle-number');

                if (number) {
                    number.textContent = index + 1;
                }

            });
        }

        function addVehicle(data = {}) {

            const fragment =
                template.content.cloneNode(true);

            const vehicle =
                fragment.querySelector('.vehicle-item');

            vehicle.dataset.index = vehicleIndex;

            const fields =
                vehicle.querySelectorAll('[data-field]');

            fields.forEach(function (field) {

                const fieldName =
                    field.dataset.field;

                field.name =
                    `vehicles[${vehicleIndex}][${fieldName}]`;

                field.value =
                    data[fieldName] ?? '';

            });

            container.appendChild(vehicle);

            vehicleIndex++;

            updateVehicleNumbers();
            updateEmptyMessage();
        }

        function removeVehicle(button) {

            const vehicle =
                button.closest('.vehicle-item');

            if (!vehicle) {
                return;
            }

            vehicle.remove();

            updateVehicleNumbers();
            updateEmptyMessage();
        }

        addButton.addEventListener(
            'click',
            function () {
                addVehicle();
            }
        );

        container.addEventListener(
            'click',
            function (event) {

                const button =
                    event.target.closest('.remove-vehicle');

                if (!button) {
                    return;
                }

                removeVehicle(button);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Khôi phục dữ liệu cũ khi validation lỗi / edit
        |--------------------------------------------------------------------------
        */

        const existingVehicles =
            @json($vehicles);

        existingVehicles.forEach(function (vehicle) {
            addVehicle(vehicle);
        });

        updateEmptyMessage();

    });
</script>
