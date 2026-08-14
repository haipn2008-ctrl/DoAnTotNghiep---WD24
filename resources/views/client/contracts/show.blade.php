@extends('layouts.client.index')

@section('title', 'Chi tiết hợp đồng | Cổng khách thuê')
@section('page_title', 'Chi tiết hợp đồng')

@section('content')

@php
    $statusConfig = match($contract->status) {
        'pending_signature' => [
            'text' => 'Chờ bạn ký',
            'class' => 'bg-amber-50 text-amber-700 border-amber-200'
        ],

        'signed' => [
            'text' => 'Đã ký',
            'class' => 'bg-blue-50 text-blue-700 border-blue-200'
        ],

        'deposit_paid' => [
            'text' => 'Đã đóng cọc',
            'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200'
        ],

        'active' => [
            'text' => 'Đang hiệu lực',
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
        ],

        'expired' => [
            'text' => 'Đã hết hạn',
            'class' => 'bg-orange-50 text-orange-700 border-orange-200'
        ],

        'terminated' => [
            'text' => 'Đã chấm dứt',
            'class' => 'bg-red-50 text-red-700 border-red-200'
        ],

        'completed' => [
            'text' => 'Đã hoàn tất',
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
        ],

        'deposit_returned' => [
            'text' => 'Đã hoàn cọc',
            'class' => 'bg-slate-100 text-slate-600 border-slate-200'
        ],

        default => [
            'text' => 'Không xác định',
            'class' => 'bg-slate-100 text-slate-600 border-slate-200'
        ],
    };
@endphp


<div class="mx-auto max-w-6xl">
    {{-- =========================
    THÔNG BÁO
    ========================== --}}

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-700">
            <p class="text-sm font-semibold">
                Có lỗi xảy ra:
            </p>

            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    {{-- =========================
        BREADCRUMB
    ========================== --}}
    <div class="mb-5">

        <a href="{{ route('client.contracts.index') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-indigo-600">

            <span>←</span>
            Quay lại hợp đồng của tôi

        </a>

    </div>


    {{-- =========================
        HEADER
    ========================== --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>

            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                CHI TIẾT HỢP ĐỒNG
            </p>

            <div class="mt-1 flex flex-wrap items-center gap-3">

                <h1 class="text-2xl font-bold text-slate-950">
                    {{ $contract->contract_code }}
                </h1>

                <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusConfig['class'] }}">
                    {{ $statusConfig['text'] }}
                </span>

            </div>

            <p class="mt-2 text-sm text-slate-500">
                Hợp đồng thuê phòng
                <span class="font-semibold text-slate-700">
                    {{ $contract->room->room_code ?? '---' }}
                </span>
            </p>

        </div>


        {{-- NÚT IN HỢP ĐỒNG --}}
        <div class="flex flex-wrap items-center gap-3">

            {{-- LỊCH SỬ --}}
            <button type="button"
                    onclick="openContractHistory()"
                    style="
                        height: 36px;
                        padding: 0 16px;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        border: 1px solid #f97316;
                        border-radius: 8px;
                        background-color: #f97316;
                        color: #ffffff;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                        box-shadow: 0 1px 2px rgba(0,0,0,.08);
                    ">

                <svg style="width:16px;height:16px;"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>

                <span>Lịch sử</span>
            </button>

            {{-- YÊU CẦU HOÀN CỌC --}}
            @if($contract->canRequestDepositRefund())
                <button type="button"
                        onclick="openRefundRequest()"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-emerald-700 hover:-translate-y-0.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
                    </svg>
                    <span>{{ $contract->deposit_status === \App\Models\Contract::DEPOSIT_REFUND_REJECTED ? 'Gửi lại yêu cầu hoàn cọc' : 'Yêu cầu hoàn cọc' }}</span>
                </button>
            @endif

            {{-- THÔNG TIN HOÀN CỌC --}}
            @if(
    $contract->isCompleted()
    || filled($contract->deposit_status)
    || filled($contract->deposit_refund_amount)
    || filled($contract->deposit_transfer_amount)
    || filled($contract->deposit_transfer_proof)
    || filled($contract->damage_proof)
    || filled($contract->deposit_damage_proof)
)
                <button type="button"
                        onclick="openDepositInfo()"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-emerald-700 hover:-translate-y-0.5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
                    </svg>
                    <span>Thông tin hoàn cọc</span>
                </button>
            @endif

            {{-- IN HỢP ĐỒNG --}}
            <a href="{{ route('client.contracts.print', $contract) }}"
            target="_blank"
            class="inline-flex items-center justify-center gap-2
                    px-4 py-2 rounded-lg
                    bg-blue-600 text-white
                    font-semibold text-sm
                    shadow-md
                    hover:bg-blue-700
                    hover:shadow-lg
                    transition-all duration-200">

                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 text-white"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/>
                </svg>

                <span class="text-white">In hợp đồng</span>
            </a>


            {{-- TẢI PDF --}}
            <a href="{{ route('client.contracts.download', $contract) }}"
            class="inline-flex h-9 items-center justify-center gap-2
                    rounded-lg bg-indigo-600 px-4
                    text-sm font-semibold text-white
                    shadow-sm transition-all duration-200
                    hover:bg-indigo-700 hover:-translate-y-0.5 hover:shadow-md">

                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/>
                </svg>

                <span>Tải PDF</span>
            </a>

        </div>

    </div>


    {{-- =========================
        THÔNG TIN NHANH
    ========================== --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- TIỀN THUÊ --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase text-slate-400">
                Tiền thuê
            </p>

            <p class="mt-2 text-lg font-bold text-emerald-600">
                {{ number_format($contract->monthly_rent) }} VNĐ
            </p>

            <p class="mt-1 text-xs text-slate-400">
                / tháng
            </p>

        </div>


        {{-- TIỀN CỌC --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase text-slate-400">
                Tiền đặt cọc
            </p>

            <p class="mt-2 text-lg font-bold text-amber-600">
                {{ number_format($contract->deposit_amount) }} VNĐ
            </p>

        </div>


        {{-- NGÀY BẮT ĐẦU --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase text-slate-400">
                Ngày bắt đầu
            </p>

            <p class="mt-2 text-lg font-bold text-slate-900">
                {{ optional($contract->start_date)->format('d/m/Y') }}
            </p>

        </div>


        {{-- NGÀY KẾT THÚC --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase text-slate-400">
                Ngày kết thúc
            </p>

            <p class="mt-2 text-lg font-bold text-slate-900">
                {{ optional($contract->end_date)->format('d/m/Y') }}
            </p>

        </div>

    </div>


    {{-- =========================
        THÔNG TIN PHÒNG + KHÁCH
    ========================== --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-2">

        {{-- PHÒNG --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h2 class="font-bold text-slate-900">
                    Thông tin phòng
                </h2>

            </div>

            <div class="space-y-4 p-6">

                <div class="flex justify-between gap-4">

                    <span class="text-sm text-slate-500">
                        Mã phòng
                    </span>

                    <span class="text-sm font-bold text-slate-900">
                        {{ $contract->room->room_code ?? '---' }}
                    </span>

                </div>


                <div class="border-t border-slate-100"></div>


                <div class="flex justify-between gap-4">

                    <span class="text-sm text-slate-500">
                        Giá phòng
                    </span>

                    <span class="text-sm font-bold text-emerald-600">
                        {{ number_format($contract->room->price ?? 0) }} VNĐ
                    </span>

                </div>

            </div>

        </div>


        {{-- KHÁCH THUÊ --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h2 class="font-bold text-slate-900">
                    Thông tin khách thuê
                </h2>

            </div>


            <div class="space-y-4 p-6">

                <div class="flex justify-between gap-4">

                    <span class="text-sm text-slate-500">
                        Họ tên
                    </span>

                    <span class="text-sm font-bold text-slate-900">
                        {{ $contract->tenant->full_name }}
                    </span>

                </div>


                <div class="border-t border-slate-100"></div>


                <div class="flex justify-between gap-4">

                    <span class="text-sm text-slate-500">
                        Điện thoại
                    </span>

                    <span class="text-sm font-medium text-slate-900">
                        {{ $contract->tenant->phone }}
                    </span>

                </div>


                <div class="border-t border-slate-100"></div>


                <div class="flex justify-between gap-4">

                    <span class="text-sm text-slate-500">
                        CCCD
                    </span>

                    <span class="text-sm font-medium text-slate-900">
                        {{ $contract->tenant->cccd ?? '---' }}
                    </span>

                </div>


                <div class="border-t border-slate-100"></div>


                <div class="flex justify-between gap-4">

                    <span class="text-sm text-slate-500">
                        Email
                    </span>

                    <span class="text-sm font-medium text-slate-900">
                        {{ $contract->tenant->email ?? '---' }}
                    </span>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================
        NỘI DUNG HỢP ĐỒNG
    ========================== --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

            <div>

                <h2 class="font-bold text-slate-900">
                    Nội dung hợp đồng
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    @if($contract->isPendingSignature())
                        Vui lòng đọc kỹ toàn bộ nội dung trước khi ký.
                    @elseif($contract->isSigned())
                        Hợp đồng đã được ký và đang chờ xác nhận tiền đặt cọc.
                    @elseif($contract->isDepositPaidStatus())
                        Hợp đồng đã được ký và hoàn tất tiền đặt cọc.
                    @elseif($contract->isActive())
                        Nội dung và các điều khoản của hợp đồng thuê phòng đang có hiệu lực.
                    @elseif($contract->isExpired())
                        Nội dung của hợp đồng thuê phòng đã hết hạn.
                    @elseif($contract->isTerminated())
                        Nội dung của hợp đồng thuê phòng đã chấm dứt.
                    @elseif($contract->isCompleted())
                        Hợp đồng đã hoàn tất và tiền đặt cọc đã được xử lý.
                    @else
                        Nội dung và các điều khoản của hợp đồng thuê phòng.
                    @endif
                </p>

            </div>

            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                Chỉ đọc
            </span>

        </div>


        <div class="p-6">

            @if(!empty($contract->contract_content))

                <div class="contract-content max-h-[700px] min-h-[500px] overflow-y-auto rounded-xl border border-slate-200 bg-white p-8 leading-8 text-slate-700">

                    {!! $contract->contract_content !!}

                </div>

            @else

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">

                    <p class="font-semibold text-amber-800">
                        Hợp đồng chưa có nội dung.
                    </p>

                </div>

            @endif

        </div>

    </div>


    {{-- =========================
        KÝ HỢP ĐỒNG
    ========================== --}}
    @if($contract->isPendingSignature())

        <div class="mt-6 overflow-hidden rounded-xl border border-indigo-200 bg-white shadow-sm">

            <div class="border-b border-indigo-100 bg-indigo-50 px-6 py-5">

                <h2 class="text-lg font-bold text-indigo-950">
                    Xác nhận ký hợp đồng
                </h2>

                <p class="mt-1 text-sm text-indigo-700">
                    Hợp đồng đang chờ chữ ký của bạn.
                </p>

            </div>


            <div class="p-6">

                {{-- CHECKBOX --}}
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">

                    <input
                        type="checkbox"
                        id="agreeContract"
                        class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600">

                    <span>

                        <span class="block text-sm font-semibold text-slate-900">
                            Tôi đã đọc và đồng ý với nội dung hợp đồng
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                            Vui lòng đọc kỹ các điều khoản trước khi thực hiện ký hợp đồng.
                        </span>

                    </span>

                </label>


                {{-- SIGNATURE --}}
                <div class="mt-6">

                    <div class="mb-2 flex items-center justify-between">

                        <label class="text-sm font-semibold text-slate-900">
                            Chữ ký khách thuê
                        </label>

                        <button type="button"
                                id="clearSignature"
                                class="text-sm font-semibold text-red-500 hover:text-red-600">

                            Xóa chữ ký

                        </button>

                    </div>


                    <div class="overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-slate-50">

                        <canvas
                            id="signatureCanvas"
                            class="h-[220px] w-full cursor-crosshair bg-white">
                        </canvas>

                    </div>


                    <p class="mt-2 text-xs text-slate-400">
                        Dùng chuột hoặc màn hình cảm ứng để ký vào khu vực phía trên.
                    </p>

                </div>


                {{-- BUTTON --}}
                <div class="mt-6 flex justify-end">

                    <button
                        type="button"
                        id="confirmSignature"
                        disabled
                        class="cursor-not-allowed rounded-lg bg-indigo-300 px-6 py-3 text-sm font-bold text-white transition">

                        Xác nhận ký hợp đồng

                    </button>

                </div>

            </div>

        </div>

    @endif
    {{-- =========================
    CHỮ KÝ ĐÃ XÁC NHẬN
    ========================== --}}

    @if(!empty($contract->tenant_signature))

        <div class="mt-6 overflow-hidden rounded-xl border border-emerald-200 bg-white shadow-sm">

            <div class="border-b border-emerald-100 bg-emerald-50 px-6 py-5">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <h2 class="text-lg font-bold text-emerald-950">
                            Chữ ký hợp đồng
                        </h2>

                        <p class="mt-1 text-sm text-emerald-700">
                            Hợp đồng đã được khách thuê xác nhận ký.
                        </p>
                    </div>

                    <span class="rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-bold text-emerald-700">
                        Đã ký
                    </span>

                </div>

            </div>


            <div class="p-6">

                <div class="grid gap-6 md:grid-cols-2">

                    {{-- THÔNG TIN KÝ --}}
                    <div>

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Người ký
                        </p>

                        <p class="mt-2 text-base font-bold text-slate-900">
                            {{ $contract->tenant->full_name ?? '---' }}
                        </p>


                        <div class="mt-5">

                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Thời gian ký
                            </p>

                            <p class="mt-2 text-sm font-semibold text-slate-900">
                                {{ optional($contract->signed_at)->format('d/m/Y H:i') ?? '---' }}
                            </p>

                        </div>

                    </div>


                    {{-- HÌNH CHỮ KÝ --}}
                    <div>

                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Chữ ký khách thuê
                        </p>

                        <div class="flex min-h-[160px] items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">

                            <img
                                src="{{ asset('storage/' . $contract->tenant_signature) }}"
                                alt="Chữ ký khách thuê"
                                class="max-h-[130px] max-w-full object-contain"
                            >

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endif
    
    {{-- =========================
        ĐĂNG KÝ / XÁC NHẬN NHẬN PHÒNG
    ========================= --}}
    @if($contract->isSigned() && !$contract->move_in_date)

        @php
            $today = now()->toDateString();
            $startDate = optional($contract->start_date)->format('Y-m-d');
            $endDate = optional(
                $contract->extend_end_date ?? $contract->end_date
            )->format('Y-m-d');

            // Nếu chưa đăng ký ngày dự kiến:
            // cho chọn từ ngày bắt đầu hợp đồng trở đi.
            $minDate = max($today, $startDate);
        @endphp

        <div class="mt-6 overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm">

            <div class="border-b border-blue-100 bg-blue-50 px-6 py-5">
                <div class="flex items-start gap-4">

                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-xl">
                        🏠
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-blue-950">
                            Xác nhận nhận phòng
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-blue-700">
                            Hợp đồng đã được ký. Bạn có thể đăng ký ngày dự kiến nhận phòng.
                        </p>
                    </div>

                </div>
            </div>


            <div class="p-6">

                {{-- THÔNG TIN HỢP ĐỒNG --}}
                <div class="grid gap-4 md:grid-cols-3 mb-6">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">
                            Phòng
                        </p>

                        <p class="mt-1 font-bold text-slate-900">
                            {{ $contract->room->room_code ?? '---' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">
                            Ngày bắt đầu hợp đồng
                        </p>

                        <p class="mt-1 font-bold text-slate-900">
                            {{ optional($contract->start_date)->format('d/m/Y') }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">
                            Ngày kết thúc hợp đồng
                        </p>

                        <p class="mt-1 font-bold text-slate-900">
                            {{ optional($contract->extend_end_date ?? $contract->end_date)->format('d/m/Y') }}
                        </p>
                    </div>

                </div>


                @if(!$contract->planned_move_in_date)

                    {{-- CHƯA CHỌN NGÀY --}}
                    <form
                        method="POST"
                        action="{{ route('client.contracts.schedule-move-in', $contract) }}"
                    >
                        @csrf

                        <div>
                            <label
                                for="planned_move_in_date"
                                class="mb-2 block text-sm font-bold text-slate-700"
                            >
                                Ngày dự kiến nhận phòng
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="date"
                                id="planned_move_in_date"
                                name="planned_move_in_date"
                                value="{{ old('planned_move_in_date') }}"
                                min="{{ $minDate }}"
                                max="{{ $endDate }}"
                                required
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Bạn có thể nhận phòng sau ngày bắt đầu hợp đồng.
                                Ví dụ: hợp đồng bắt đầu 10/08 nhưng bạn có thể đăng ký nhận phòng ngày 30/08.
                            </p>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-blue-700"
                            >
                                📅 Đăng ký ngày nhận phòng
                            </button>
                        </div>

                    </form>

                @else

                    {{-- ĐÃ ĐĂNG KÝ NGÀY DỰ KIẾN --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                        <div class="flex items-start gap-3">

                            <div class="text-2xl">
                                ⏳
                            </div>

                            <div>
                                <h3 class="font-bold text-amber-900">
                                    Chờ nhận phòng
                                </h3>

                                <p class="mt-1 text-sm text-amber-800">
                                    Ngày dự kiến nhận phòng:
                                    <strong>
                                        {{ $contract->planned_move_in_date->format('d/m/Y') }}
                                    </strong>
                                </p>

                                @if(now()->toDateString() < $contract->planned_move_in_date->format('Y-m-d'))

                                    <p class="mt-2 text-xs text-amber-700">
                                        Chưa đến ngày nhận phòng.
                                        Khi đến ngày dự kiến, bạn mới có thể xác nhận đã nhận phòng.
                                    </p>

                                @else

                                    <form
                                        method="POST"
                                        action="{{ route('client.contracts.confirm-move-in', $contract) }}"
                                        class="mt-4"
                                        onsubmit="return confirm('Bạn xác nhận hôm nay đã thực tế nhận phòng?');"
                                    >
                                        @csrf

                                        <label
                                            for="move_in_date"
                                            class="mb-2 block text-sm font-bold text-slate-700"
                                        >
                                            Ngày thực tế nhận phòng
                                        </label>

                                        <input
                                            type="date"
                                            id="move_in_date"
                                            name="move_in_date"
                                            value="{{ now()->toDateString() }}"
                                            min="{{ $contract->planned_move_in_date->format('Y-m-d') }}"
                                            max="{{ now()->toDateString() }}"
                                            required
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                                        >

                                        <button
                                            type="submit"
                                            style="
                                                margin-top: 16px;
                                                display: inline-flex;
                                                align-items: center;
                                                gap: 8px;
                                                padding: 12px 24px;
                                                border: 0;
                                                border-radius: 12px;
                                                background: #16a34a !important;
                                                color: #ffffff !important;
                                                font-size: 14px;
                                                font-weight: 700;
                                                line-height: 1.25;
                                                cursor: pointer;
                                                box-shadow: 0 2px 6px rgba(22,163,74,.25);
                                            "
                                        >
                                            🏠 Xác nhận đã nhận phòng
                                        </button>

                                    </form>

                                @endif

                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </div>

    @endif
    
    {{-- UPDATED --}}
    <p class="mt-5 text-right text-xs text-slate-400">
        Cập nhật lần cuối:
        {{ optional($contract->updated_at)->format('d/m/Y H:i') }}
    </p>

</div>



{{-- =========================
    MODAL GỬI YÊU CẦU HOÀN CỌC
========================= --}}
@if($contract->canRequestDepositRefund())
<style>
    #refundRequestModal{
        position:fixed;
        inset:0;
        z-index:100001;
        display:none;
        align-items:center;
        justify-content:center;
        padding:18px;
    }
    #refundRequestModal.is-open{display:flex}
    .rr-backdrop{
        position:absolute;
        inset:0;
        background:rgba(15,23,42,.62);
        backdrop-filter:blur(3px);
    }
    .rr-dialog{
        position:relative;
        z-index:1;
        width:min(760px,calc(100vw - 28px));
        max-height:calc(100vh - 36px);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:20px;
        box-shadow:0 28px 90px rgba(15,23,42,.32);
    }
    .rr-header{
        flex:none;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        padding:18px 22px;
        border-bottom:1px solid #e2e8f0;
        background:#fff;
    }
    .rr-header-left{display:flex;align-items:center;gap:12px;min-width:0}
    .rr-icon{
        width:44px;height:44px;flex:0 0 44px;
        display:grid;place-items:center;
        border-radius:12px;
        background:#ecfdf5;color:#059669;
    }
    .rr-title{margin:0;color:#0f172a;font-size:18px;font-weight:800}
    .rr-subtitle{margin:3px 0 0;color:#64748b;font-size:12px}
    .rr-close{
        width:38px;height:38px;
        display:grid;place-items:center;
        border:0;border-radius:10px;
        background:transparent;color:#94a3b8;cursor:pointer;
    }
    .rr-close:hover{background:#f1f5f9;color:#334155}
    .rr-body{min-height:0;overflow-y:auto;padding:22px;background:#f8fafc}
    .rr-card{
        border:1px solid #e2e8f0;
        border-radius:14px;
        background:#fff;
        padding:16px;
    }
    .rr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .rr-field{display:flex;flex-direction:column;gap:7px}
    .rr-field-full{grid-column:1/-1}
    .rr-label{font-size:13px;font-weight:700;color:#334155}
    .rr-required{color:#ef4444}
    .rr-input,.rr-textarea{
        width:100%;
        border:1px solid #cbd5e1;
        border-radius:10px;
        background:#fff;
        color:#0f172a;
        font-size:13px;
        outline:none;
        transition:.15s;
        box-sizing:border-box;
    }
    .rr-input{height:42px;padding:0 12px}
    .rr-textarea{min-height:90px;padding:11px 12px;resize:vertical}
    .rr-input:focus,.rr-textarea:focus{
        border-color:#10b981;
        box-shadow:0 0 0 3px rgba(16,185,129,.10);
    }
    .rr-hint{font-size:11px;line-height:1.5;color:#64748b}
    .rr-summary{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:12px;
        margin-bottom:16px;
    }
    .rr-summary-item{
        border-radius:12px;
        padding:13px 14px;
        border:1px solid #e2e8f0;
        background:#f8fafc;
    }
    .rr-summary-label{
        font-size:11px;
        color:#64748b;
        text-transform:uppercase;
        font-weight:700;
        letter-spacing:.03em;
    }
    .rr-summary-value{
        margin-top:5px;
        font-size:18px;
        font-weight:800;
        color:#0f172a;
    }
    .rr-info{
        margin-bottom:16px;
        padding:13px 14px;
        border:1px solid #bfdbfe;
        border-radius:12px;
        background:#eff6ff;
        color:#1e40af;
        font-size:12px;
        line-height:1.6;
    }
    .rr-footer{
        flex:none;
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:10px;
        padding:13px 18px;
        border-top:1px solid #e2e8f0;
        background:#fff;
    }
    .rr-btn{
        height:40px;
        padding:0 16px;
        border-radius:9px;
        font-size:13px;
        font-weight:700;
        cursor:pointer;
    }
    .rr-btn-cancel{
        border:1px solid #cbd5e1;
        background:#fff;
        color:#334155;
    }
    .rr-btn-cancel:hover{background:#f8fafc}
    .rr-btn-submit{
        border:1px solid #059669;
        background:#059669;
        color:#fff;
        box-shadow:0 1px 2px rgba(0,0,0,.08);
    }
    .rr-btn-submit:hover{background:#047857}
    @media(max-width:640px){
        #refundRequestModal{padding:8px}
        .rr-dialog{width:100%;max-height:calc(100vh - 16px);border-radius:14px}
        .rr-header{padding:14px 15px}
        .rr-body{padding:14px}
        .rr-grid,.rr-summary{grid-template-columns:1fr}
        .rr-field-full{grid-column:auto}
        .rr-footer{padding:11px 14px}
    }
</style>

<div id="refundRequestModal" role="dialog" aria-modal="true" aria-labelledby="refundRequestTitle">
    <div class="rr-backdrop" onclick="closeRefundRequest()"></div>

    <div class="rr-dialog" onclick="event.stopPropagation()">
        <div class="rr-header">
            <div class="rr-header-left">
                <div class="rr-icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
                    </svg>
                </div>

                <div>
                    <h2 id="refundRequestTitle" class="rr-title">Gửi yêu cầu hoàn cọc</h2>
                    <p class="rr-subtitle">
                        {{ $contract->contract_code }} · Phòng {{ $contract->room->room_code ?? '---' }}
                    </p>
                </div>
            </div>

            <button type="button" class="rr-close" onclick="closeRefundRequest()" aria-label="Đóng">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST"
              action="{{ route('client.deposit-refunds.store', $contract) }}"
              enctype="multipart/form-data"
              id="refundRequestForm">
            @csrf

            <div class="rr-body">
                <div class="rr-summary">
                    <div class="rr-summary-item">
                        <div class="rr-summary-label">Tiền đặt cọc</div>
                        <div class="rr-summary-value" style="color:#d97706">
                            {{ number_format((float) $contract->deposit_amount, 0, ',', '.') }} VNĐ
                        </div>
                    </div>

                    <div class="rr-summary-item">
                        <div class="rr-summary-label">Trạng thái hợp đồng</div>
                        <div class="rr-summary-value" style="font-size:15px;color:#dc2626">
                            {{ $statusConfig['text'] }}
                        </div>
                    </div>
                </div>

                <div class="rr-info">
                    Hợp đồng đã chấm dứt. Vui lòng nhập chính xác thông tin tài khoản
                    để Admin sử dụng khi hoàn tiền cọc. Sau khi gửi, trạng thái sẽ chuyển
                    sang <strong>Chờ Admin xử lý</strong>.
                </div>

                <div class="rr-card">
                    <div class="rr-grid">
                        <div class="rr-field">
                            <label for="refund_bank_name" class="rr-label">
                                Ngân hàng <span class="rr-required">*</span>
                            </label>
                            <input
                                id="refund_bank_name"
                                type="text"
                                name="bank_name"
                                required
                                maxlength="100"
                                value="{{ old('bank_name', $contract->deposit_bank_name) }}"
                                placeholder="Ví dụ: Vietcombank"
                                class="rr-input"
                            >
                        </div>

                        <div class="rr-field">
                            <label for="refund_bank_account_number" class="rr-label">
                                Số tài khoản <span class="rr-required">*</span>
                            </label>
                            <input
                                id="refund_bank_account_number"
                                type="text"
                                name="bank_account_number"
                                required
                                maxlength="50"
                                value="{{ old('bank_account_number', $contract->deposit_bank_account_number) }}"
                                placeholder="Nhập số tài khoản nhận tiền"
                                class="rr-input"
                            >
                        </div>

                        <div class="rr-field rr-field-full">
                            <label for="refund_bank_account_name" class="rr-label">
                                Chủ tài khoản <span class="rr-required">*</span>
                            </label>
                            <input
                                id="refund_bank_account_name"
                                type="text"
                                name="bank_account_name"
                                required
                                maxlength="150"
                                value="{{ old('bank_account_name', $contract->deposit_bank_account_name) }}"
                                placeholder="Nhập đúng tên chủ tài khoản"
                                class="rr-input"
                            >
                        </div>

                        <div class="rr-field rr-field-full">
                            <label for="refund_qr_image" class="rr-label">
                                Ảnh QR ngân hàng
                                <span style="font-weight:400;color:#94a3b8">(không bắt buộc)</span>
                            </label>
                            <input
                                id="refund_qr_image"
                                type="file"
                                name="qr_image"
                                accept="image/png,image/jpeg,image/webp"
                                class="rr-input"
                                style="padding:9px 10px"
                            >
                            <div class="rr-hint">
                                JPG, PNG hoặc WEBP · tối đa 5MB. Có thể bỏ qua nếu không dùng QR.
                            </div>
                        </div>

                        <div class="rr-field rr-field-full">
                            <label for="refund_note" class="rr-label">
                                Ghi chú
                            </label>
                            <textarea
                                id="refund_note"
                                name="note"
                                maxlength="1000"
                                class="rr-textarea"
                                placeholder="Ví dụ: Vui lòng chuyển tiền vào tài khoản trên."
                            >{{ old('note', $contract->deposit_process_note) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rr-footer">
                <button type="button" class="rr-btn rr-btn-cancel" onclick="closeRefundRequest()">
                    Hủy
                </button>

                <button type="submit"
                        class="rr-btn rr-btn-submit"
                        onclick="return confirm('Bạn xác nhận thông tin tài khoản nhận tiền là chính xác và muốn gửi yêu cầu hoàn cọc?');">
                    Gửi yêu cầu hoàn cọc
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('refundRequestModal');

    window.openRefundRequest = function () {
        if (!modal) return;
        modal.classList.add('is-open');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';

        const firstInput = document.getElementById('refund_bank_name');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 50);
        }
    };

    window.closeRefundRequest = function () {
        if (!modal) return;
        modal.classList.remove('is-open');
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
            closeRefundRequest();
        }
    });
})();
</script>
@endif

{{-- =========================
    MODAL THÔNG TIN HOÀN CỌC
========================= --}}
@php
    $depositAmount = (float) ($contract->deposit_amount ?? 0);
    $deductionAmount = (float) ($contract->deposit_deduction_amount ?? 0);
    $approvedRefundAmount = (float) ($contract->deposit_refund_amount ?? max($depositAmount - $deductionAmount, 0));
    $transferredAmount = (float) ($contract->deposit_transfer_amount ?? 0);
    $displayRefundAmount = $transferredAmount > 0 ? $transferredAmount : $approvedRefundAmount;

    $refundStatusText = match($contract->deposit_status) {
        \App\Models\Contract::DEPOSIT_REFUND_REQUESTED => 'Chờ Admin xử lý',
        \App\Models\Contract::DEPOSIT_REFUND_APPROVED => 'Đã duyệt hoàn cọc',
        \App\Models\Contract::DEPOSIT_REFUND_REJECTED => 'Đã từ chối',
        \App\Models\Contract::DEPOSIT_REFUND_PROCESSING => 'Đang xử lý',
        \App\Models\Contract::DEPOSIT_RETURNED => 'Đã hoàn cọc',
        \App\Models\Contract::DEPOSIT_PARTIAL => 'Đã hoàn cọc một phần',
        \App\Models\Contract::DEPOSIT_FORFEITED => 'Không hoàn cọc',
        default => $contract->isCompleted() ? 'Đã hoàn tất' : 'Chưa xử lý',
    };

    $refundStatusClass = match($contract->deposit_status) {
        \App\Models\Contract::DEPOSIT_REFUND_REJECTED,
        \App\Models\Contract::DEPOSIT_FORFEITED => 'bg-red-50 text-red-700 border-red-200',
        \App\Models\Contract::DEPOSIT_REFUND_APPROVED,
        \App\Models\Contract::DEPOSIT_REFUND_PROCESSING => 'bg-blue-50 text-blue-700 border-blue-200',
        \App\Models\Contract::DEPOSIT_RETURNED,
        \App\Models\Contract::DEPOSIT_PARTIAL => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        default => 'bg-amber-50 text-amber-700 border-amber-200',
    };

    $damageProof = $contract->damage_proof ?? $contract->deposit_damage_proof ?? null;
@endphp

@if(
    $contract->isCompleted()
    || filled($contract->deposit_status)
    || filled($contract->deposit_refund_amount)
    || filled($contract->deposit_transfer_amount)
    || filled($contract->deposit_transfer_proof)
    || filled($contract->damage_proof)
    || filled($contract->deposit_damage_proof)
)
<style>
    #depositInfoModal{position:fixed;inset:0;z-index:100000;display:none;align-items:center;justify-content:center;padding:18px}
    #depositInfoModal.is-open{display:flex}
    .dr-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.62);backdrop-filter:blur(3px)}
    .dr-dialog{position:relative;z-index:1;width:min(1050px,calc(100vw - 28px));max-height:calc(100vh - 36px);display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 28px 90px rgba(15,23,42,.32)}
    .dr-header{flex:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 22px;border-bottom:1px solid #e2e8f0;background:#fff}
    .dr-header-left{display:flex;align-items:center;gap:12px;min-width:0}
    .dr-icon{width:44px;height:44px;flex:0 0 44px;display:grid;place-items:center;border-radius:12px;background:#ecfdf5;color:#059669}
    .dr-title{margin:0;color:#0f172a;font-size:18px;font-weight:800}
    .dr-subtitle{margin:3px 0 0;color:#64748b;font-size:12px}
    .dr-close{width:38px;height:38px;display:grid;place-items:center;border:0;border-radius:10px;background:transparent;color:#94a3b8;cursor:pointer}
    .dr-close:hover{background:#f1f5f9;color:#334155}
    .dr-body{min-height:0;overflow-y:auto;padding:22px;background:#f8fafc}
    .dr-grid{display:grid;gap:16px}
    .dr-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
    .dr-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
    .dr-card{border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:16px}
    .dr-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#94a3b8}
    .dr-value{margin-top:6px;font-size:15px;font-weight:700;color:#0f172a;overflow-wrap:anywhere}
    .dr-money{font-size:21px}
    .dr-money-amber{border-color:#fde68a;background:#fffbeb}
    .dr-money-red{border-color:#fecaca;background:#fef2f2}
    .dr-money-green{border-color:#a7f3d0;background:#ecfdf5}
    .dr-section-title{margin:0;font-size:15px;font-weight:800;color:#0f172a}
    .dr-section-sub{margin:4px 0 0;font-size:12px;color:#64748b}
    .dr-bank-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:14px}
    .dr-bank-item{border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;padding:12px}
    .dr-proof{margin-top:14px;border:1px solid #dbeafe;border-radius:14px;background:#eff6ff;padding:16px}
    .dr-proof-inner{margin-top:12px;display:flex;align-items:center;justify-content:center;min-height:230px;overflow:hidden;border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:10px}
    .dr-proof-img{display:block;max-width:100%;max-height:420px;width:auto;height:auto;object-fit:contain;border-radius:8px}
    .dr-empty{display:flex;align-items:center;justify-content:center;min-height:120px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;color:#64748b;font-size:13px;text-align:center;padding:20px}
    .dr-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
    .dr-footer{flex:none;display:flex;align-items:center;justify-content:flex-end;padding:13px 18px;border-top:1px solid #e2e8f0;background:#fff}
    .dr-btn{height:38px;padding:0 16px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#334155;font-size:13px;font-weight:700;cursor:pointer}
    .dr-btn:hover{background:#f1f5f9}
    .dr-link{display:inline-flex;align-items:center;gap:7px;margin-top:12px;padding:9px 13px;border-radius:9px;background:#059669;color:#fff;font-size:12px;font-weight:700;text-decoration:none}
    .dr-link:hover{background:#047857}
    @media(max-width:800px){
        .dr-grid-3,.dr-grid-2,.dr-bank-grid,.dr-meta{grid-template-columns:1fr}
    }
    @media(max-width:640px){
        #depositInfoModal{padding:8px}
        .dr-dialog{width:100%;max-height:calc(100vh - 16px);border-radius:14px}
        .dr-header{padding:14px 15px}
        .dr-body{padding:14px}
    }
</style>

<div id="depositInfoModal" role="dialog" aria-modal="true" aria-labelledby="depositInfoTitle">
    <div class="dr-backdrop" onclick="closeDepositInfo()"></div>

    <div class="dr-dialog" onclick="event.stopPropagation()">
        <div class="dr-header">
            <div class="dr-header-left">
                <div class="dr-icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
                    </svg>
                </div>

                <div>
                    <h2 id="depositInfoTitle" class="dr-title">Thông tin hoàn cọc</h2>
                    <p class="dr-subtitle">
                        {{ $contract->contract_code }} · Phòng {{ $contract->room->room_code ?? '---' }}
                    </p>
                </div>
            </div>

            <button type="button" class="dr-close" onclick="closeDepositInfo()" aria-label="Đóng">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="dr-body">
            {{-- TRẠNG THÁI + TIỀN --}}
            <div class="dr-grid dr-grid-3">
                <div class="dr-card dr-money-amber">
                    <p class="dr-label">Tiền cọc ban đầu</p>
                    <p class="dr-value dr-money" style="color:#b45309">
                        {{ number_format($depositAmount, 0, ',', '.') }} VNĐ
                    </p>
                </div>

                <div class="dr-card dr-money-red">
                    <p class="dr-label" style="color:#dc2626">Số tiền khấu trừ</p>
                    <p class="dr-value dr-money" style="color:#dc2626">
                        {{ number_format($deductionAmount, 0, ',', '.') }} VNĐ
                    </p>
                    @if($contract->deposit_process_reason)
                        <p class="mt-2 text-xs leading-5 text-red-700">
                            {{ $contract->deposit_process_reason }}
                        </p>
                    @endif
                </div>

                <div class="dr-card dr-money-green">
                    <p class="dr-label" style="color:#059669">
                        {{ $transferredAmount > 0 ? 'Số tiền đã chuyển' : 'Số tiền được hoàn' }}
                    </p>
                    <p class="dr-value dr-money" style="color:#059669">
                        {{ number_format($displayRefundAmount, 0, ',', '.') }} VNĐ
                    </p>
                </div>
            </div>

            {{-- TRẠNG THÁI --}}
            <div class="dr-card" style="margin-top:16px">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="dr-section-title">Trạng thái xử lý</h3>
                        <p class="dr-section-sub">Kết quả xử lý tiền đặt cọc của hợp đồng.</p>
                    </div>

                    <span class="inline-flex rounded-full border px-3 py-1.5 text-xs font-bold {{ $refundStatusClass }}">
                        {{ $refundStatusText }}
                    </span>
                </div>
            </div>

            {{-- TÀI KHOẢN NHẬN TIỀN --}}
            <div class="dr-card" style="margin-top:16px">
                <h3 class="dr-section-title">Thông tin nhận tiền</h3>
                <p class="dr-section-sub">Tài khoản khách thuê đã cung cấp để nhận tiền hoàn cọc.</p>

                <div class="dr-bank-grid">
                    <div class="dr-bank-item">
                        <p class="dr-label">Ngân hàng</p>
                        <p class="dr-value">{{ $contract->deposit_bank_name ?? '---' }}</p>
                    </div>

                    <div class="dr-bank-item">
                        <p class="dr-label">Số tài khoản</p>
                        <p class="dr-value">{{ $contract->deposit_bank_account_number ?? '---' }}</p>
                    </div>

                    <div class="dr-bank-item">
                        <p class="dr-label">Chủ tài khoản</p>
                        <p class="dr-value">{{ $contract->deposit_bank_account_name ?? '---' }}</p>
                    </div>
                </div>

                @if(filled($contract->deposit_qr_image))
                    <div class="mt-4">
                        <p class="dr-label">QR ngân hàng</p>
                        <div class="mt-2 flex justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <img src="{{ route('client.deposit-refunds.qr', $contract) }}"
                                 alt="QR ngân hàng"
                                 class="max-h-[220px] max-w-full rounded-lg border border-slate-200 bg-white object-contain">
                        </div>
                    </div>
                @endif
            </div>

            {{-- ẢNH MINH CHỨNG HƯ HỎNG --}}
            <div class="dr-proof" style="margin-top:16px;border-color:#fecaca;background:#fff7ed">
                <h3 class="dr-section-title">Ảnh minh chứng hư hỏng / thiệt hại</h3>
                <p class="dr-section-sub">Minh chứng cho khoản tiền bị khấu trừ khỏi tiền cọc.</p>

                @if(filled($damageProof))
                    <div class="dr-proof-inner">
                        <img src="{{ asset('storage/' . ltrim($damageProof, '/')) }}"
                             alt="Ảnh minh chứng hư hỏng / thiệt hại"
                             class="dr-proof-img">
                    </div>
                @else
                    <div class="dr-empty" style="margin-top:12px">
                        Không có ảnh minh chứng hư hỏng được lưu.
                    </div>
                @endif
            </div>

            {{-- ẢNH XÁC MINH CHUYỂN KHOẢN --}}
            <div class="dr-proof" style="margin-top:16px">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="dr-section-title">Ảnh xác minh chuyển khoản</h3>
                        <p class="dr-section-sub">Bằng chứng Admin đã thực tế chuyển tiền hoàn cọc cho khách thuê.</p>
                    </div>

                    @if(filled($contract->deposit_transfer_proof))
                        <a href="{{ route('client.deposit-refunds.proof', $contract) }}"
                           target="_blank"
                           rel="noopener"
                           class="dr-link" style="margin-top:0">
                            Xem ảnh đầy đủ
                        </a>
                    @endif
                </div>

                @if(filled($contract->deposit_transfer_proof))
                    <div class="dr-proof-inner">
                        <img src="{{ route('client.deposit-refunds.proof', $contract) }}"
                             alt="Ảnh xác minh chuyển khoản hoàn cọc"
                             class="dr-proof-img">
                    </div>
                @else
                    <div class="dr-empty" style="margin-top:12px">
                        Chưa có ảnh xác minh chuyển khoản.
                    </div>
                @endif
            </div>

            {{-- LÝ DO / GHI CHÚ --}}
            @if($contract->deposit_process_reason || $contract->deposit_process_note || $contract->deposit_admin_note)
                <div class="dr-grid dr-grid-2" style="margin-top:16px">
                    @if($contract->deposit_process_reason)
                        <div class="dr-card">
                            <p class="dr-label">Lý do xử lý</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">
                                {{ $contract->deposit_process_reason }}
                            </p>
                        </div>
                    @endif

                    @if($contract->deposit_process_note || $contract->deposit_admin_note)
                        <div class="dr-card">
                            <p class="dr-label">Ghi chú xử lý</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">
                                {{ $contract->deposit_process_note ?? $contract->deposit_admin_note }}
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- THỜI GIAN --}}
            <div class="dr-meta" style="margin-top:16px">
                @if($contract->deposit_refund_requested_at)
                    <div class="dr-card">
                        <p class="dr-label">Khách yêu cầu hoàn cọc</p>
                        <p class="dr-value">{{ $contract->deposit_refund_requested_at->format('d/m/Y H:i') }}</p>
                    </div>
                @endif

                @if($contract->deposit_refund_approved_at)
                    <div class="dr-card">
                        <p class="dr-label">Admin duyệt hoàn cọc</p>
                        <p class="dr-value">{{ $contract->deposit_refund_approved_at->format('d/m/Y H:i') }}</p>
                    </div>
                @endif

                @if($contract->deposit_transferred_at)
                    <div class="dr-card">
                        <p class="dr-label">Admin chuyển khoản</p>
                        <p class="dr-value">{{ $contract->deposit_transferred_at->format('d/m/Y H:i') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="dr-footer">
            <button type="button" class="dr-btn" onclick="closeDepositInfo()">Đóng</button>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('depositInfoModal');

    window.openDepositInfo = function () {
        if (!modal) return;
        modal.classList.add('is-open');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
    };

    window.closeDepositInfo = function () {
        if (!modal) return;
        modal.classList.remove('is-open');
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
            closeDepositInfo();
        }
    });
})();
</script>
@endif

{{-- MODAL LỊCH SỬ HỢP ĐỒNG --}}
<style>
#contractHistoryModal{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:24px}
#contractHistoryModal.is-open{display:flex}
.ch-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58);backdrop-filter:blur(2px)}
.ch-dialog{position:relative;z-index:1;width:min(900px,calc(100vw - 32px));height:min(720px,calc(100vh - 48px));display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 24px 70px rgba(15,23,42,.28)}
.ch-header{flex:none;display:flex;align-items:center;justify-content:space-between;gap:20px;padding:17px 22px;border-bottom:1px solid #e2e8f0;background:#fff}
.ch-header-left{display:flex;align-items:center;gap:12px;min-width:0}
.ch-header-icon{width:42px;height:42px;flex:0 0 42px;display:grid;place-items:center;border-radius:12px;background:#eef2ff;color:#4f46e5}
.ch-title{margin:0;color:#0f172a;font-size:17px;font-weight:700}.ch-subtitle{margin:3px 0 0;color:#64748b;font-size:12px}
.ch-close{width:36px;height:36px;display:grid;place-items:center;border:0;border-radius:9px;background:transparent;color:#94a3b8;cursor:pointer}.ch-close:hover{background:#f1f5f9;color:#334155}
.ch-filters{flex:none;padding:14px 22px;border-bottom:1px solid #e2e8f0;background:#f8fafc}
.ch-filter-grid{display:grid;grid-template-columns:minmax(210px,1.4fr) minmax(155px,.9fr) 145px 145px auto;gap:9px}
.ch-control{width:100%;height:38px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;padding:0 11px;color:#334155;font-size:12px;outline:none}
.ch-control:focus{border-color:#818cf8;box-shadow:0 0 0 3px rgba(99,102,241,.10)}
.ch-reset{height:38px;padding:0 13px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#475569;font-size:12px;font-weight:600;cursor:pointer}.ch-reset:hover{background:#f1f5f9}
.ch-body{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:20px 26px 4px;background:#fff}
.ch-item{position:relative;display:grid;grid-template-columns:20px minmax(0,1fr);gap:14px;padding-bottom:22px}
.ch-item:not(:last-child)::before{content:"";position:absolute;left:9px;top:18px;bottom:0;width:1px;background:#e2e8f0}
.ch-dot-wrap{position:relative;z-index:1;display:flex;justify-content:center;padding-top:5px;background:#fff}.ch-dot{width:10px;height:10px;border-radius:999px;box-shadow:0 0 0 4px #fff}
.ch-main{min-width:0}.ch-row{display:flex;align-items:flex-start;justify-content:space-between;gap:18px}.ch-action{margin:0;color:#0f172a;font-size:14px;font-weight:700;line-height:1.45}.ch-time{flex:none;padding-top:2px;color:#94a3b8;font-size:11px;white-space:nowrap}
.ch-description{margin:4px 0 0;color:#64748b;font-size:12.5px;line-height:1.5}
.ch-reason{margin-top:9px;padding:9px 11px;border:1px solid #fde7b0;border-radius:9px;background:#fffbeb}.ch-reason-label{display:block;margin-bottom:2px;color:#b45309;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.ch-reason-text{margin:0;color:#475569;font-size:12.5px;line-height:1.5;overflow-wrap:anywhere}
.ch-changes{margin-top:10px;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;background:#f8fafc}
.ch-change{display:grid;grid-template-columns:150px minmax(0,1fr) 20px minmax(0,1fr);gap:9px;align-items:start;padding:9px 11px;font-size:12px}
.ch-change+.ch-change{border-top:1px solid #e2e8f0}.ch-field{font-weight:700;color:#475569}.ch-old{color:#ef4444;overflow-wrap:anywhere}.ch-arrow{color:#94a3b8;text-align:center}.ch-new{color:#059669;font-weight:600;overflow-wrap:anywhere}
.ch-meta{display:flex;align-items:center;gap:6px;margin-top:8px;color:#94a3b8;font-size:11px}.ch-meta strong{color:#475569;font-weight:600}
.ch-empty{padding:55px 20px;color:#64748b;text-align:center;font-size:13px}
.ch-footer{flex:none;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:13px 20px;border-top:1px solid #e2e8f0;background:#f8fafc}.ch-count{color:#64748b;font-size:11px}.ch-btn-close{padding:8px 16px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#334155;font-size:13px;font-weight:600;cursor:pointer}.ch-btn-close:hover{background:#f1f5f9}
@media(max-width:850px){.ch-filter-grid{grid-template-columns:1fr 1fr}.ch-reset{width:100%}}
@media(max-width:640px){#contractHistoryModal{padding:10px}.ch-dialog{width:100%;height:calc(100vh - 20px);border-radius:14px}.ch-header{padding:14px 15px}.ch-filters{padding:11px 15px}.ch-filter-grid{grid-template-columns:1fr}.ch-body{padding:16px 15px 4px}.ch-row{display:block}.ch-time{display:block;margin-top:3px}.ch-change{grid-template-columns:1fr}.ch-arrow{display:none}.ch-footer{padding:11px 15px}}
</style>

<div id="contractHistoryModal" role="dialog" aria-modal="true" aria-labelledby="contractHistoryTitle">
    <div class="ch-backdrop" onclick="closeContractHistory()"></div>
    <div class="ch-dialog" onclick="event.stopPropagation()">
        <div class="ch-header">
            <div class="ch-header-left">
                <div class="ch-header-icon">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 id="contractHistoryTitle" class="ch-title">Nhật ký hợp đồng</h2>
                    <p class="ch-subtitle">{{ $contract->contract_code }} · {{ $contract->histories->count() }} hoạt động</p>
                </div>
            </div>
            <button type="button" class="ch-close" onclick="closeContractHistory()" aria-label="Đóng">
                <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="ch-filters">
            <div class="ch-filter-grid">
                <input id="historySearch" class="ch-control" type="search" placeholder="Tìm nội dung, lý do, người thực hiện...">
                <select id="historyType" class="ch-control">

                    <option value="">
                        Tất cả hoạt động
                    </option>

                    <option value="contract">
                        Hợp đồng
                    </option>

                    <option value="signature">
                        Ký hợp đồng
                    </option>

                    <option value="edit">
                        Chỉnh sửa hợp đồng
                    </option>

                    <option value="recall">
                        Thu hồi hợp đồng
                    </option>

                    <option value="deposit">
                        Tiền đặt cọc
                    </option>

                    <option value="extension">
                        Gia hạn
                    </option>

                    <option value="termination">
                        Trả phòng / chấm dứt
                    </option>

                    <option value="cancellation">
                        Hủy hợp đồng
                    </option>

                    <option value="other">
                        Hoạt động khác
                    </option>

                </select>
                <input id="historyFrom" class="ch-control" type="date" title="Từ ngày">
                <input id="historyTo" class="ch-control" type="date" title="Đến ngày">
                <button type="button" class="ch-reset" onclick="resetHistoryFilters()">Đặt lại</button>
            </div>
        </div>

        <div class="ch-body" id="historyList">
            @forelse($contract->histories as $history)
                @php
                    /*
                    |--------------------------------------------------------------------------
                    | Chuẩn hóa action
                    |--------------------------------------------------------------------------
                    */
                    $action = strtolower(trim((string) ($history->action ?? '')));

                    /*
                    |--------------------------------------------------------------------------
                    | Mapping action -> Tên hiển thị / màu / nhóm lọc
                    |--------------------------------------------------------------------------
                    */
                    $hc = match($action) {

                    /*
                    |--------------------------------------------------------------------------
                    | HỢP ĐỒNG
                    |--------------------------------------------------------------------------
                    */

                    'created',
                    'contract_created'
                        => [
                            'Hợp đồng được tạo',
                            '#64748b',
                            'contract'
                        ],

                    'updated',
                    'contract_updated'
                        => [
                            'Thông tin hợp đồng được cập nhật',
                            '#3b82f6',
                            'edit'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | GỬI KÝ / KÝ HỢP ĐỒNG
                    |--------------------------------------------------------------------------
                    */

                    // Action THỰC TẾ hiện có trong database
                    'send_signature',

                    // Hỗ trợ tên chuẩn nếu backend dùng sau này
                    'sent_for_signature',
                    'contract_sent_for_signature'
                        => [
                            'Gửi hợp đồng cho khách thuê ký',
                            '#64748b',
                            'signature'
                        ],

                    'signed',
                    'contract_signed'
                        => [
                            'Khách thuê đã ký hợp đồng',
                            '#10b981',
                            'signature'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | THU HỒI HỢP ĐỒNG
                    |--------------------------------------------------------------------------
                    */

                    // Action THỰC TẾ trong database
                    'recall_signature',

                    // Hỗ trợ action khác
                    'recalled',
                    'recalled_for_edit',
                    'contract_recalled'
                        => [
                            'Thu hồi hợp đồng để chỉnh sửa',
                            '#f59e0b',
                            'recall'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | TIỀN ĐẶT CỌC
                    |--------------------------------------------------------------------------
                    */

                    // Action THỰC TẾ trong database
                    'deposit_paid',

                    'deposit_confirmed',
                    'deposit_received'
                        => [
                            'Đã xác nhận tiền đặt cọc',
                            '#06b6d4',
                            'deposit'
                        ],

                    // Action THỰC TẾ trong database
                    'return_deposit',

                    'deposit_returned',
                    'deposit_processed',
                    'process_deposit'
                        => [
                            'Đã xử lý tiền đặt cọc',
                            '#8b5cf6',
                            'deposit'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | KÍCH HOẠT HỢP ĐỒNG
                    |--------------------------------------------------------------------------
                    */

                    // Action THỰC TẾ trong database
                    'activated',

                    'active',
                    'contract_activated'
                        => [
                            'Hợp đồng bắt đầu có hiệu lực',
                            '#10b981',
                            'activation'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | GIA HẠN
                    |--------------------------------------------------------------------------
                    */

                    'extension_requested',
                    'request_extension'
                        => [
                            'Khách thuê gửi yêu cầu gia hạn',
                            '#3b82f6',
                            'extension'
                        ],

                    // Action THỰC TẾ trong database
                    'extend',

                    'extended',
                    'extension_approved'
                        => [
                            'Hợp đồng đã được gia hạn',
                            '#10b981',
                            'extension'
                        ],

                    'extension_rejected',
                    'reject_extension'
                        => [
                            'Yêu cầu gia hạn bị từ chối',
                            '#ef4444',
                            'extension'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | TRẢ PHÒNG / CHẤM DỨT
                    |--------------------------------------------------------------------------
                    */

                    'termination_requested',
                    'return_room_requested',
                    'request_termination'
                        => [
                            'Khách thuê gửi yêu cầu trả phòng',
                            '#f59e0b',
                            'termination'
                        ],

                    'termination_approved',
                    'return_room_approved'
                        => [
                            'Yêu cầu trả phòng được chấp nhận',
                            '#10b981',
                            'termination'
                        ],

                    'termination_rejected',
                    'return_room_rejected'
                        => [
                            'Yêu cầu trả phòng bị từ chối',
                            '#ef4444',
                            'termination'
                        ],

                    // Action THỰC TẾ trong database
                    'terminate',

                    'terminated',
                    'contract_terminated'
                        => [
                            'Kết thúc hợp đồng',
                            '#ef4444',
                            'termination'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | HỦY HỢP ĐỒNG
                    |--------------------------------------------------------------------------
                    */

                    'cancelled',
                    'canceled',
                    'contract_cancelled',
                    'contract_canceled'
                        => [
                            'Hợp đồng đã bị hủy',
                            '#ef4444',
                            'cancellation'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | HOÀN TẤT
                    |--------------------------------------------------------------------------
                    */

                    'completed',
                    'contract_completed'
                        => [
                            'Hợp đồng đã hoàn tất',
                            '#10b981',
                            'contract'
                        ],


                    /*
                    |--------------------------------------------------------------------------
                    | KHÔNG XÁC ĐỊNH
                    |--------------------------------------------------------------------------
                    */

                    default => [
                        $history->description ?: 'Cập nhật hợp đồng',
                        '#94a3b8',
                        'other'
                    ],
                };

                    $oldData = is_array($history->old_data)
                        ? $history->old_data
                        : (json_decode($history->old_data ?? '[]', true) ?: []);

                    $newData = is_array($history->new_data)
                        ? $history->new_data
                        : (json_decode($history->new_data ?? '[]', true) ?: []);

                    $changeKeys = array_values(
                        array_unique(
                            array_merge(
                                array_keys($oldData),
                                array_keys($newData)
                            )
                        )
                    );

                    $fieldLabels = [
                        'status' => 'Trạng thái',
                        'monthly_rent' => 'Tiền thuê',
                        'deposit_amount' => 'Tiền đặt cọc',
                        'start_date' => 'Ngày bắt đầu',
                        'end_date' => 'Ngày kết thúc',
                        'room_id' => 'Phòng',
                        'tenant_id' => 'Khách thuê',
                        'note' => 'Ghi chú',
                        'contract_content' => 'Nội dung hợp đồng',
                        'deposit_status' => 'Trạng thái tiền cọc',
                        'refund_amount' => 'Số tiền hoàn',
                        'deduction_amount' => 'Số tiền khấu trừ',
                    ];

                    $actor = $history->user->name ?? 'Hệ thống';

                    $dateValue = optional($history->created_at)->format('Y-m-d');

                    $searchText = strtolower(trim(
                        $hc[0]
                        .' '
                        .($history->description ?? '')
                        .' '
                        .($history->reason ?? '')
                        .' '
                        .$actor
                        .' '
                        .$action
                    ));
                @endphp

                <div class="ch-item history-entry"
                    data-action="{{ $action }}"
                    data-category="{{ $hc[2] }}"
                    data-date="{{ $dateValue }}"
                    data-search="{{ e($searchText) }}">
                    <div class="ch-dot-wrap"><span class="ch-dot" style="background:{{ $hc[1] }}"></span></div>
                    <div class="ch-main">
                        <div class="ch-row">
                            <h3 class="ch-action">{{ $hc[0] }}</h3>
                            <time class="ch-time">{{ optional($history->created_at)->format('d/m/Y H:i') }}</time>
                        </div>

                        @if($history->description && $history->description !== $hc[0])
                            <p class="ch-description">{{ $history->description }}</p>
                        @endif

                        @if($history->reason)
                            <div class="ch-reason">
                                <span class="ch-reason-label">Lý do / ghi chú</span>
                                <p class="ch-reason-text">{{ $history->reason }}</p>
                            </div>
                        @endif

                        @if(in_array($action, ['updated', 'contract_updated', 'edited']) && count($changeKeys))
                            <div class="ch-changes">
                                @foreach($changeKeys as $key)
                                    @php
                                        $old = $oldData[$key] ?? null;
                                        $new = $newData[$key] ?? null;
                                        $displayOld = is_array($old) ? json_encode($old, JSON_UNESCAPED_UNICODE) : ($old ?? '—');
                                        $displayNew = is_array($new) ? json_encode($new, JSON_UNESCAPED_UNICODE) : ($new ?? '—');
                                    @endphp
                                    @if((string)$displayOld !== (string)$displayNew)
                                        <div class="ch-change">
                                            <div class="ch-field">{{ $fieldLabels[$key] ?? ucfirst(str_replace('_',' ', $key)) }}</div>
                                            <div class="ch-old">{{ $displayOld }}</div>
                                            <div class="ch-arrow">→</div>
                                            <div class="ch-new">{{ $displayNew }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="ch-meta">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0"/></svg>
                            <span>Thực hiện bởi</span><strong>{{ $actor }}</strong>
                        </div>
                    </div>
                </div>
            @empty
                <div class="ch-empty">Chưa có lịch sử hợp đồng.</div>
            @endforelse

            <div id="historyNoResults" class="ch-empty" style="display:none">Không tìm thấy hoạt động phù hợp.</div>
        </div>

        <div class="ch-footer">
            <span class="ch-count" id="historyCount"></span>
            <button type="button" class="ch-btn-close" onclick="closeContractHistory()">Đóng</button>
        </div>
    </div>
</div>

<script>
(function(){
    const modal=document.getElementById('contractHistoryModal');
    const search=document.getElementById('historySearch');
    const type=document.getElementById('historyType');
    const from=document.getElementById('historyFrom');
    const to=document.getElementById('historyTo');
    const count=document.getElementById('historyCount');
    const noResults=document.getElementById('historyNoResults');

    function filterHistory(){
        const q=(search?.value||'').trim().toLowerCase();
        const category=type?.value||'';
        const fromDate=from?.value||'';
        const toDate=to?.value||'';
        const entries=[...document.querySelectorAll('.history-entry')];
        let visible=0;

        entries.forEach(item=>{
            const text=(item.dataset.search||'').toLowerCase();
            const itemCategory=item.dataset.category||'';
            const date=item.dataset.date||'';
            const okSearch=!q||text.includes(q);
            const okType=!category||itemCategory===category;
            const okFrom=!fromDate||date>=fromDate;
            const okTo=!toDate||date<=toDate;
            const show=okSearch&&okType&&okFrom&&okTo;
            item.style.display=show?'grid':'none';
            if(show) visible++;
        });

        if(count) count.textContent=`Hiển thị ${visible} / ${entries.length} hoạt động`;
        if(noResults) noResults.style.display=(entries.length && visible===0)?'block':'none';
    }

    [search,type,from,to].forEach(el=>{
        if(!el)return;
        el.addEventListener(el.tagName==='INPUT'&&el.type==='search'?'input':'change',filterHistory);
    });

    window.resetHistoryFilters=function(){
        if(search)search.value='';
        if(type)type.value='';
        if(from)from.value='';
        if(to)to.value='';
        filterHistory();
    };

    window.openContractHistory=function(){
        if(!modal)return;
        modal.classList.add('is-open');
        document.documentElement.style.overflow='hidden';
        document.body.style.overflow='hidden';
        filterHistory();
    };

    window.closeContractHistory=function(){
        if(!modal)return;
        modal.classList.remove('is-open');
        document.documentElement.style.overflow='';
        document.body.style.overflow='';
    };

    document.addEventListener('keydown',e=>{
        if(e.key==='Escape'&&modal?.classList.contains('is-open')) closeContractHistory();
    });

    filterHistory();
})();
</script>

{{-- =========================
    SIGNATURE SCRIPT
========================== --}}
@if($contract->isPendingSignature())

<script>
document.addEventListener('DOMContentLoaded', function () {

    const canvas = document.getElementById('signatureCanvas');
    const clearButton = document.getElementById('clearSignature');
    const agreeCheckbox = document.getElementById('agreeContract');
    const confirmButton = document.getElementById('confirmSignature');

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    let drawing = false;
    let hasSignature = false;


    function resizeCanvas() {

        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();

        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;

        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

    }


    resizeCanvas();


    function getPosition(event) {

        const rect = canvas.getBoundingClientRect();

        const clientX = event.touches
            ? event.touches[0].clientX
            : event.clientX;

        const clientY = event.touches
            ? event.touches[0].clientY
            : event.clientY;

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };

    }


    function startDrawing(event) {

        event.preventDefault();

        drawing = true;

        const position = getPosition(event);

        ctx.beginPath();
        ctx.moveTo(position.x, position.y);

    }


    function draw(event) {

        if (!drawing) {
            return;
        }

        event.preventDefault();

        const position = getPosition(event);

        ctx.lineTo(position.x, position.y);
        ctx.stroke();

        hasSignature = true;

        updateButton();

    }


    function stopDrawing() {
        drawing = false;
        ctx.beginPath();
    }


    function updateButton() {

        const enabled =
            agreeCheckbox.checked &&
            hasSignature;

        confirmButton.disabled = !enabled;

        if (enabled) {

            confirmButton.classList.remove(
                'bg-indigo-300',
                'cursor-not-allowed'
            );

            confirmButton.classList.add(
                'bg-indigo-600',
                'hover:bg-indigo-700',
                'cursor-pointer'
            );

        } else {

            confirmButton.classList.add(
                'bg-indigo-300',
                'cursor-not-allowed'
            );

            confirmButton.classList.remove(
                'bg-indigo-600',
                'hover:bg-indigo-700',
                'cursor-pointer'
            );

        }

    }


    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);

    window.addEventListener('mouseup', stopDrawing);


    canvas.addEventListener(
        'touchstart',
        startDrawing,
        { passive: false }
    );

    canvas.addEventListener(
        'touchmove',
        draw,
        { passive: false }
    );

    canvas.addEventListener(
        'touchend',
        stopDrawing
    );


    clearButton.addEventListener('click', function () {

        ctx.clearRect(
            0,
            0,
            canvas.width,
            canvas.height
        );

        hasSignature = false;

        updateButton();

    });


    agreeCheckbox.addEventListener(
        'change',
        updateButton
    );


    /*
     * Tạm thời chưa gửi database.
     * Bước tiếp theo sẽ POST chữ ký về Laravel.
     */
    confirmButton.addEventListener('click', function () {

        if (!agreeCheckbox.checked || !hasSignature) {
            return;
        }

        // Chống bấm 2 lần
        confirmButton.disabled = true;
        confirmButton.textContent = 'Đang ký...';

        const form = document.createElement('form');

        form.method = 'POST';
        form.action = @json(route('client.contracts.sign', $contract));

        // CSRF
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = @json(csrf_token());

        // Ảnh chữ ký
        const signature = document.createElement('input');
        signature.type = 'hidden';
        signature.name = 'signature';
        signature.value = canvas.toDataURL('image/png');

        form.appendChild(csrf);
        form.appendChild(signature);

        document.body.appendChild(form);

        form.submit();
    });

});
</script>

@endif

@endsection