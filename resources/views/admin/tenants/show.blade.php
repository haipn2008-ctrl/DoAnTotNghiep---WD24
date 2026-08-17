@extends('layouts.admin.index')

@section('title', 'Chi tiết khách thuê | Quản lý phòng trọ')
@section('page_title', 'Chi tiết khách thuê')

@php
    $tenant->loadMissing([
        'user',
        'contracts.room',
        'memberContracts.room',
        'vehicles',
        'temporaryResidences.contract.room',
    ]);

    $allContracts = $tenant->contracts
        ->concat($tenant->memberContracts)
        ->unique('id')
        ->sortByDesc('id')
        ->values();

    $activeContract = $allContracts
        ->whereIn('status', ['active', 'expired'])
        ->first();

    $contractStatusLabels = [
        'draft' => [
            'label' => 'Bản nháp',
            'class' => 'bg-slate-50 text-slate-700 ring-slate-200',
        ],
        'pending_signature' => [
            'label' => 'Chờ ký',
            'class' => 'bg-amber-50 text-amber-700 ring-amber-200',
        ],
        'signed' => [
            'label' => 'Đã ký',
            'class' => 'bg-blue-50 text-blue-700 ring-blue-200',
        ],
        'deposit_paid' => [
            'label' => 'Đã thanh toán cọc',
            'class' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        ],
        'active' => [
            'label' => 'Đang hiệu lực',
            'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ],
        'expired' => [
            'label' => 'Hết hạn',
            'class' => 'bg-amber-50 text-amber-700 ring-amber-200',
        ],
        'terminated' => [
            'label' => 'Đã kết thúc',
            'class' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ],
        'completed' => [
            'label' => 'Hoàn tất',
            'class' => 'bg-purple-50 text-purple-700 ring-purple-200',
        ],
    ];

    $temporaryResidenceStatusLabels = [
        'active' => [
            'label' => 'Đang tạm trú',
            'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ],
        'expired' => [
            'label' => 'Đã hết hạn',
            'class' => 'bg-amber-50 text-amber-700 ring-amber-200',
        ],
        'cancelled' => [
            'label' => 'Đã hủy',
            'class' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ],
        'pending' => [
            'label' => 'Chờ xử lý',
            'class' => 'bg-slate-50 text-slate-700 ring-slate-200',
        ],
    ];

    $infoItems = [
        [
            'label' => 'Ngày sinh',
            'value' => $tenant->date_of_birth
                ? \Carbon\Carbon::parse($tenant->date_of_birth)->format('d/m/Y')
                : 'Chưa cập nhật',
        ],
        [
            'label' => 'Giới tính',
            'value' => [
                'male' => 'Nam',
                'female' => 'Nữ',
                'other' => 'Khác',
            ][$tenant->gender] ?? 'Chưa cập nhật',
        ],
        [
            'label' => 'CCCD',
            'value' => $tenant->cccd ?: 'Chưa cập nhật',
        ],
        [
            'label' => 'Ngày cấp CCCD',
            'value' => $tenant->cccd_issue_date
                ? \Carbon\Carbon::parse($tenant->cccd_issue_date)->format('d/m/Y')
                : 'Chưa cập nhật',
        ],
        [
            'label' => 'Nơi cấp CCCD',
            'value' => $tenant->cccd_issue_place ?: 'Chưa cập nhật',
        ],
        [
            'label' => 'Số điện thoại',
            'value' => $tenant->phone ?: 'Chưa cập nhật',
        ],
        [
            'label' => 'Email',
            'value' => $tenant->email ?: 'Chưa cập nhật',
        ],
        [
            'label' => 'Địa chỉ',
            'value' => $tenant->address ?: 'Chưa cập nhật',
        ],
        [
            'label' => 'Tài khoản',
            'value' => $tenant->user
                ? $tenant->user->name . ' (' . $tenant->user->email . ')'
                : 'Chưa gắn tài khoản',
        ],
    ];
@endphp

@section('content')

    <div class="space-y-6">


        {{-- =========================================================
        HEADER
        ========================================================== --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-medium text-slate-500">
                    Hồ sơ khách thuê
                </p>

                <h2 class="mt-1 text-2xl font-bold text-slate-950">
                    {{ $tenant->full_name }}
                </h2>
            </div>

            <div class="flex flex-wrap gap-2">

                <a href="{{ route('admin.tenants.edit', $tenant) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-edit text-lg"></i>
                    Cập nhật
                </a>

                <a href="{{ route('admin.tenants.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Quay lại
                </a>

            </div>
        </div>


        {{-- =========================================================
        THÔNG TIN CƠ BẢN + THÔNG TIN CÁ NHÂN
        ========================================================== --}}
        <div class="grid gap-6 lg:grid-cols-[320px_1fr]">

            {{-- Hồ sơ tổng quan --}}
            <section class="rounded-lg border border-slate-200 bg-white p-5 text-center shadow-sm">

                <div
                    class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-indigo-50 text-4xl font-bold text-indigo-700 ring-1 ring-indigo-100">
                    {{ mb_substr($tenant->full_name ?? 'K', 0, 1) }}
                </div>

                <h3 class="mt-4 text-xl font-bold text-slate-950">
                    {{ $tenant->full_name }}
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $tenant->phone ?: 'Chưa cập nhật SĐT' }}
                </p>

                <div class="mt-5">
                    @if ($activeContract)

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                            Đang thuê phòng
                            {{ $activeContract->room->room_code ?? 'N/A' }}
                        </span>

                    @else

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-600 ring-1 ring-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>

                            Chưa thuê phòng
                        </span>

                    @endif
                </div>

            </section>


            {{-- Thông tin cá nhân --}}
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">
                        Thông tin cá nhân
                    </h3>

                    <p class="text-sm text-slate-500">
                        Thông tin định danh và liên hệ.
                    </p>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2">

                    @foreach ($infoItems as $item)

                        <div class="{{ $item['label'] === 'Địa chỉ' ? 'sm:col-span-2' : '' }} rounded-lg bg-slate-50 p-4">
                            <p class="text-sm font-medium text-slate-500">
                                {{ $item['label'] }}
                            </p>

                            <p class="mt-2 break-words font-semibold text-slate-950">
                                {{ $item['value'] }}
                            </p>
                        </div>

                    @endforeach

                </div>

            </section>

        </div>


        {{-- =========================================================
        PHƯƠNG TIỆN
        ========================================================== --}}
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <div class="flex items-center justify-between gap-3">

                    <div>
                        <h3 class="font-semibold text-slate-950">
                            Phương tiện
                        </h3>

                        <p class="text-sm text-slate-500">
                            Danh sách phương tiện của khách thuê.
                        </p>
                    </div>

                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">
                        {{ $tenant->vehicles->count() }} xe
                    </span>

                </div>
            </div>

            @if ($tenant->vehicles->count())

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Loại xe
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Tên xe
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Biển số
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Màu
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Ghi chú
                                </th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach ($tenant->vehicles as $vehicle)

                                <tr class="hover:bg-slate-50">

                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        {{ $vehicle->vehicle_type ?: '---' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm font-medium text-slate-950">
                                        {{ $vehicle->vehicle_name ?: '---' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm font-semibold text-slate-950">
                                        {{ $vehicle->license_plate ?: '---' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        {{ $vehicle->color ?: '---' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-slate-500">
                                        {{ $vehicle->note ?: '---' }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="px-5 py-8 text-center text-sm text-slate-500">
                    Khách thuê chưa đăng ký phương tiện.
                </div>

            @endif

        </section>


        {{-- =========================================================
        LỊCH SỬ HỢP ĐỒNG
        ========================================================== --}}
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h3 class="font-semibold text-slate-950">
                    Lịch sử hợp đồng
                </h3>

                <p class="text-sm text-slate-500">
                    Các hợp đồng đã gắn với khách thuê này.
                </p>

            </div>

            <div class="divide-y divide-slate-100">

                @forelse ($allContracts as $contract)

                        @php
                            $contractStatus = $contractStatusLabels[$contract->status]
                                ?? [
                                    'label' => $contract->status,
                                    'class' => 'bg-slate-50 text-slate-700 ring-slate-200',
                                ];
                        @endphp

                        <div class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center">

                            <div>

                                <div class="flex flex-wrap items-center gap-2">

                                    <a href="{{ route('admin.contracts.show', $contract) }}"
                                        class="font-semibold text-slate-950 hover:text-indigo-700 hover:underline">
                                        {{ $contract->contract_code }}
                                    </a>

                                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">
                                        {{ (int) ($contract->representative_tenant_id ?: $contract->tenant_id) === (int) $tenant->id ? 'Người đại diện' : 'Thành viên' }}
                                    </span>

                                </div>

                                <p class="mt-1 text-sm text-slate-500">

                                    Phòng
                                    {{ $contract->room->room_code ?? 'N/A' }}

                                    ·

                                    {{ $contract->start_date
                    ? \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y')
                    : '---'
                                        }}

                                    -

                                    {{ $contract->end_date
                    ? \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y')
                    : '---'
                                        }}

                                </p>

                            </div>

                            <span
                                class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $contractStatus['class'] }}">
                                {{ $contractStatus['label'] }}
                            </span>

                        </div>

                @empty

                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                        Khách thuê chưa đứng tên hoặc tham gia hợp đồng nào.
                    </div>

                @endforelse

            </div>

        </section>


        {{-- =========================================================
        HỒ SƠ ĐĂNG KÝ TẠM TRÚ
        ========================================================== --}}
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">

                    <div>

                        <h3 class="font-semibold text-slate-950">
                            Hồ sơ đăng ký tạm trú
                        </h3>

                        <p class="text-sm text-slate-500">
                            Danh sách các hồ sơ tạm trú của khách thuê.
                        </p>

                    </div>

                    <span class="w-fit rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">
                        {{ $tenant->temporaryResidences->count() }} hồ sơ
                    </span>

                </div>

            </div>


            @if ($tenant->temporaryResidences->count())

                <div class="divide-y divide-slate-100">

                    @foreach ($tenant->temporaryResidences as $temporaryResidence)

                        @php
                            $residenceStatus = $temporaryResidenceStatusLabels[$temporaryResidence->status]
                                ?? [
                                    'label' => $temporaryResidence->status ?: 'Không xác định',
                                    'class' => 'bg-slate-50 text-slate-700 ring-slate-200',
                                ];
                        @endphp

                        <div class="px-5 py-5">

                            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">

                                {{-- Thông tin chính --}}
                                <div class="min-w-0 flex-1">

                                    <div class="flex flex-wrap items-center gap-2">

                                        <h4 class="font-semibold text-slate-950">
                                            Hồ sơ tạm trú #{{ $temporaryResidence->id }}
                                        </h4>

                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $residenceStatus['class'] }}">
                                            {{ $residenceStatus['label'] }}
                                        </span>

                                    </div>


                                    {{-- Hợp đồng --}}
                                    @if ($temporaryResidence->contract)

                                        <p class="mt-2 text-sm text-slate-500">

                                            Hợp đồng:

                                            <span class="font-semibold text-slate-700">
                                                {{ $temporaryResidence->contract->contract_code }}
                                            </span>

                                            · Phòng

                                            <span class="font-semibold text-slate-700">
                                                {{ $temporaryResidence->contract->room->room_code ?? 'N/A' }}
                                            </span>

                                        </p>

                                    @else

                                        <p class="mt-2 text-sm text-slate-500">
                                            Chưa gắn với hợp đồng.
                                        </p>

                                    @endif


                                    {{-- Thời gian --}}
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">

                                        <div class="rounded-lg bg-slate-50 p-3">

                                            <p class="text-xs font-medium text-slate-500">
                                                Ngày bắt đầu
                                            </p>

                                            <p class="mt-1 text-sm font-semibold text-slate-950">

                                                {{ $temporaryResidence->start_date
                        ? \Carbon\Carbon::parse($temporaryResidence->start_date)->format('d/m/Y')
                        : 'Chưa cập nhật'
                                                        }}

                                            </p>

                                        </div>


                                        <div class="rounded-lg bg-slate-50 p-3">

                                            <p class="text-xs font-medium text-slate-500">
                                                Ngày kết thúc
                                            </p>

                                            <p class="mt-1 text-sm font-semibold text-slate-950">

                                                {{ $temporaryResidence->end_date
                        ? \Carbon\Carbon::parse($temporaryResidence->end_date)->format('d/m/Y')
                        : 'Chưa xác định'
                                                        }}

                                            </p>

                                        </div>

                                    </div>


                                    {{-- Ghi chú --}}
                                    @if ($temporaryResidence->note)

                                        <div class="mt-3 rounded-lg bg-slate-50 p-3">

                                            <p class="text-xs font-medium text-slate-500">
                                                Ghi chú
                                            </p>

                                            <p class="mt-1 text-sm text-slate-700">
                                                {{ $temporaryResidence->note }}
                                            </p>

                                        </div>

                                    @endif

                                </div>


                                {{-- Ngày tạo --}}
                                <div class="shrink-0 text-sm text-slate-500 lg:text-right">

                                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                        Ngày tạo hồ sơ
                                    </p>

                                    <p class="mt-1 font-medium text-slate-700">

                                        {{ $temporaryResidence->created_at
                        ? $temporaryResidence->created_at->format('d/m/Y H:i')
                        : '---'
                                                }}

                                    </p>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="px-5 py-10 text-center">

                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">

                        <i class="bx bx-file text-2xl"></i>

                    </div>

                    <p class="mt-3 text-sm font-medium text-slate-700">
                        Chưa có hồ sơ đăng ký tạm trú
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Khách thuê này chưa có dữ liệu tạm trú trong hệ thống.
                    </p>

                </div>

            @endif

        </section>

    </div>

@endsection
