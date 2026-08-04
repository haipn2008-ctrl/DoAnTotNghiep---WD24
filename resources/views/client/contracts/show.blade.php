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
                    @elseif($contract->isDepositReturnedStatus())
                        Hợp đồng đã chấm dứt và tiền đặt cọc đã được hoàn trả.
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
    
    
    {{-- UPDATED --}}
    <p class="mt-5 text-right text-xs text-slate-400">
        Cập nhật lần cuối:
        {{ optional($contract->updated_at)->format('d/m/Y H:i') }}
    </p>

</div>



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