@extends('layouts.admin.index')

@section('content')

<div class="space-y-6">

{{-- ================================================================
     HEADER
================================================================= --}}

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

    <div>
        <div class="flex items-center gap-3">

            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <i class="fas fa-house-user text-lg"></i>
            </div>

            <div>
                <h1 class="text-xl font-bold text-slate-950">
                    Đăng ký tạm trú
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Tạo hồ sơ đăng ký tạm trú cho khách thuê.
                </p>
            </div>

        </div>
    </div>

    <a href="{{ route('admin.temporary-residences.index') }}"
       class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">

        <i class="fas fa-arrow-left"></i>
        Quay lại

    </a>

</div>


{{-- ================================================================
     VALIDATION ERROR
================================================================= --}}

@if ($errors->any())

    <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">

        <div class="flex items-start gap-3">

            <i class="bx bx-error-circle mt-0.5 text-xl text-rose-600"></i>

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


{{-- ================================================================
     FORM
================================================================= --}}

<form action="{{ route('admin.temporary-residences.store') }}"
      method="POST">

    @csrf


    {{-- ============================================================
         1. THÔNG TIN KHÁCH THUÊ
    ============================================================= --}}

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-5 py-4">

            <h3 class="font-semibold text-slate-950">
                1. Thông tin khách thuê
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Chọn khách thuê và hợp đồng thuê tương ứng.
            </p>

        </div>


        <div class="p-5">

            <div class="grid gap-5 md:grid-cols-2">

                {{-- KHÁCH THUÊ --}}

                <div>

                    <label for="tenant_id"
                           class="mb-1.5 block text-sm font-semibold text-slate-700">

                        Khách thuê
                        <span class="text-rose-500">*</span>

                    </label>

                    <select
                        id="tenant_id"
                        name="tenant_id"
                        required
                        class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 @error('tenant_id') border-rose-400 @enderror"
                    >

                        <option value="">
                            Chọn khách thuê
                        </option>

                        @foreach ($tenants as $tenant)

                            <option
                                value="{{ $tenant->id }}"
                                @selected(old('tenant_id') == $tenant->id)
                            >
                                {{ $tenant->full_name }}
                                -
                                {{ $tenant->phone }}
                                -
                                CCCD: {{ $tenant->cccd }}
                            </option>

                        @endforeach

                    </select>

                    @error('tenant_id')

                        <p class="mt-1 text-sm text-rose-600">
                            {{ $message }}
                        </p>

                    @enderror

                    <p class="mt-1 text-xs text-slate-400">
                        Chọn khách thuê cần đăng ký tạm trú.
                    </p>

                </div>


                {{-- HỢP ĐỒNG --}}

                <div>

                    <label for="contract_id"
                           class="mb-1.5 block text-sm font-semibold text-slate-700">

                        Hợp đồng thuê
                        <span class="text-rose-500">*</span>

                    </label>

                    <select
                        id="contract_id"
                        name="contract_id"
                        required
                        class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 @error('contract_id') border-rose-400 @enderror"
                    >

                        <option value="">
                            Chọn hợp đồng
                        </option>

                        @foreach ($contracts as $contract)

                            <option
                                value="{{ $contract->id }}"
                                data-tenant="{{ $contract->tenant_id }}"
                                data-room="{{ $contract->room->room_number ?? '' }}"
                                @selected(old('contract_id') == $contract->id)
                            >
                                Hợp đồng #{{ $contract->id }}

                                @if ($contract->room)
                                    - Phòng {{ $contract->room->room_number }}
                                @endif
                            </option>

                        @endforeach

                    </select>

                    @error('contract_id')

                        <p class="mt-1 text-sm text-rose-600">
                            {{ $message }}
                        </p>

                    @enderror

                    <p class="mt-1 text-xs text-slate-400">
                        Chỉ hiển thị hợp đồng của khách thuê đã chọn.
                    </p>

                </div>

            </div>


            {{-- PHÒNG THUÊ --}}

            <div class="mt-5">

                <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                    Phòng thuê
                </label>

                <div id="room_box"
                     class="flex min-h-[72px] items-center gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 transition">

                    <div id="room_icon"
                         class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm">

                        <i class="fas fa-door-open"></i>

                    </div>

                    <div class="min-w-0">

                        <p id="room_title"
                           class="text-sm font-semibold text-slate-500">

                            Chưa chọn phòng

                        </p>

                        <p id="room_description"
                           class="mt-0.5 text-xs text-slate-400">

                            Phòng sẽ được xác định tự động từ hợp đồng.

                        </p>

                    </div>

                    <input
                        type="hidden"
                        id="room_display"
                        value=""
                    >

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         2. THỜI GIAN TẠM TRÚ
    ============================================================= --}}

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-5 py-4">

            <h3 class="font-semibold text-slate-950">
                2. Thời gian tạm trú
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Xác định thời gian bắt đầu và kết thúc đăng ký tạm trú.
            </p>

        </div>


        <div class="grid gap-5 p-5 md:grid-cols-2">

            {{-- NGÀY BẮT ĐẦU --}}

            <div>

                <label for="start_date"
                       class="mb-1.5 block text-sm font-semibold text-slate-700">

                    Ngày bắt đầu
                    <span class="text-rose-500">*</span>

                </label>

                <input
                    id="start_date"
                    type="date"
                    name="start_date"
                    value="{{ old('start_date') }}"
                    required
                    class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 @error('start_date') border-rose-400 @enderror"
                >

                @error('start_date')

                    <p class="mt-1 text-sm text-rose-600">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- NGÀY KẾT THÚC --}}

            <div>

                <label for="end_date"
                       class="mb-1.5 block text-sm font-semibold text-slate-700">

                    Ngày kết thúc

                </label>

                <input
                    id="end_date"
                    type="date"
                    name="end_date"
                    value="{{ old('end_date') }}"
                    class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 @error('end_date') border-rose-400 @enderror"
                >

                @error('end_date')

                    <p class="mt-1 text-sm text-rose-600">
                        {{ $message }}
                    </p>

                @enderror

                <p class="mt-1 text-xs text-slate-400">
                    Có thể bỏ trống nếu chưa xác định ngày kết thúc.
                </p>

            </div>

        </div>

    </div>


    {{-- ============================================================
         3. TRẠNG THÁI & GHI CHÚ
    ============================================================= --}}

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-5 py-4">

            <h3 class="font-semibold text-slate-950">
                3. Trạng thái & ghi chú
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Cập nhật trạng thái và thông tin bổ sung cho hồ sơ.
            </p>

        </div>


        <div class="grid gap-5 p-5 md:grid-cols-2">

            {{-- TRẠNG THÁI --}}

            <div>

                <label for="status"
                       class="mb-1.5 block text-sm font-semibold text-slate-700">

                    Trạng thái
                    <span class="text-rose-500">*</span>

                </label>

                <select
                    id="status"
                    name="status"
                    required
                    class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 @error('status') border-rose-400 @enderror"
                >

                    <option value="pending"
                        @selected(old('status') == 'pending')>
                        Chờ xử lý
                    </option>

                    <option value="active"
                        @selected(old('status', 'active') == 'active')>
                        Đang hoạt động
                    </option>

                    <option value="expired"
                        @selected(old('status') == 'expired')>
                        Hết hạn
                    </option>

                    <option value="cancelled"
                        @selected(old('status') == 'cancelled')>
                        Đã hủy
                    </option>

                </select>

                @error('status')

                    <p class="mt-1 text-sm text-rose-600">
                        {{ $message }}
                    </p>

                @enderror

                <p class="mt-1 text-xs text-slate-400">
                    Trạng thái hiện tại của hồ sơ tạm trú.
                </p>

            </div>


            {{-- GHI CHÚ --}}

            <div>

                <label for="note"
                       class="mb-1.5 block text-sm font-semibold text-slate-700">

                    Ghi chú

                </label>

                <textarea
                    id="note"
                    name="note"
                    rows="4"
                    maxlength="1000"
                    placeholder="Nhập ghi chú liên quan đến đăng ký tạm trú..."
                    class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 @error('note') border-rose-400 @enderror"
                >{{ old('note') }}</textarea>

                <div class="mt-1 flex items-center justify-between">

                    @error('note')

                        <p class="text-sm text-rose-600">
                            {{ $message }}
                        </p>

                    @else

                        <span class="text-xs text-slate-400">
                            Thông tin không bắt buộc.
                        </span>

                    @enderror

                    <span id="note_counter"
                          class="text-xs text-slate-400">
                        0/1000
                    </span>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         ACTIONS
    ============================================================= --}}

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">

        <a href="{{ route('admin.temporary-residences.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">

            <i class="bx bx-x"></i>
            Hủy

        </a>

        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100"
        >

            <i class="bx bx-save"></i>
            Lưu đăng ký

        </button>

    </div>

</form>


</div>

{{-- ================================================================
JAVASCRIPT
================================================================= --}}

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const tenantSelect = document.getElementById('tenant_id');
    const contractSelect = document.getElementById('contract_id');

    const roomBox = document.getElementById('room_box');
    const roomIcon = document.getElementById('room_icon');
    const roomTitle = document.getElementById('room_title');
    const roomDescription = document.getElementById('room_description');

    const note = document.getElementById('note');
    const noteCounter = document.getElementById('note_counter');

    const oldContract = @json(old('contract_id'));


    /*
    |--------------------------------------------------------------------------
    | Cập nhật thông tin phòng
    |--------------------------------------------------------------------------
    */

    function updateRoom() {

        const selectedOption =
            contractSelect.options[contractSelect.selectedIndex];

        if (
            selectedOption &&
            selectedOption.value &&
            !selectedOption.hidden
        ) {

            const room = selectedOption.dataset.room || '';

            if (room) {

                roomTitle.textContent = 'Phòng ' + room;

                roomDescription.textContent =
                    'Phòng được xác định từ hợp đồng đã chọn.';

                roomBox.classList.remove(
                    'border-dashed',
                    'border-slate-200',
                    'bg-slate-50'
                );

                roomBox.classList.add(
                    'border-indigo-200',
                    'bg-indigo-50'
                );

                roomIcon.classList.remove(
                    'bg-white',
                    'text-slate-400'
                );

                roomIcon.classList.add(
                    'bg-indigo-100',
                    'text-indigo-600'
                );

                roomTitle.classList.remove(
                    'text-slate-500'
                );

                roomTitle.classList.add(
                    'text-indigo-700'
                );

            } else {

                resetRoom();

            }

        } else {

            resetRoom();

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Reset phòng
    |--------------------------------------------------------------------------
    */

    function resetRoom() {

        roomTitle.textContent = 'Chưa chọn phòng';

        roomDescription.textContent =
            'Phòng sẽ được xác định tự động từ hợp đồng.';

        roomBox.classList.remove(
            'border-indigo-200',
            'bg-indigo-50'
        );

        roomBox.classList.add(
            'border-dashed',
            'border-slate-200',
            'bg-slate-50'
        );

        roomIcon.classList.remove(
            'bg-indigo-100',
            'text-indigo-600'
        );

        roomIcon.classList.add(
            'bg-white',
            'text-slate-400'
        );

        roomTitle.classList.remove(
            'text-indigo-700'
        );

        roomTitle.classList.add(
            'text-slate-500'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Lọc hợp đồng theo khách thuê
    |--------------------------------------------------------------------------
    */

    function filterContracts() {

        const tenantId = tenantSelect.value;

        Array.from(contractSelect.options).forEach(function (option) {

            if (!option.value) {

                option.hidden = false;

                return;

            }

            const optionTenant = option.dataset.tenant;

            if (
                tenantId &&
                optionTenant === tenantId
            ) {

                option.hidden = false;

            } else {

                option.hidden = true;

                if (option.selected) {
                    option.selected = false;
                }

            }

        });


        if (!tenantId) {

            contractSelect.value = '';

            resetRoom();

            return;

        }

        updateRoom();

    }


    /*
    |--------------------------------------------------------------------------
    | Chọn khách thuê
    |--------------------------------------------------------------------------
    */

    tenantSelect.addEventListener('change', function () {

        contractSelect.value = '';

        filterContracts();

    });


    /*
    |--------------------------------------------------------------------------
    | Chọn hợp đồng
    |--------------------------------------------------------------------------
    */

    contractSelect.addEventListener('change', function () {

        updateRoom();

    });


    /*
    |--------------------------------------------------------------------------
    | Đếm ký tự ghi chú
    |--------------------------------------------------------------------------
    */

    function updateNoteCounter() {

        const length = note.value.length;

        noteCounter.textContent =
            length + '/1000';

    }

    note.addEventListener(
        'input',
        updateNoteCounter
    );


    /*
    |--------------------------------------------------------------------------
    | Khôi phục dữ liệu khi validation lỗi
    |--------------------------------------------------------------------------
    */

    if (tenantSelect.value) {

        filterContracts();

        if (oldContract) {

            contractSelect.value = oldContract;

            updateRoom();

        }

    }


    updateNoteCounter();

});

</script>

@endpush

@endsection
