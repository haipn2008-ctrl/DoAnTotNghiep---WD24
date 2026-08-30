@extends('layouts.client.index')

@section('title', 'Yêu cầu hoàn cọc | Cổng khách thuê')
@section('page_title', 'Yêu cầu hoàn cọc')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('client.contracts.index') }}"
               class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-indigo-600"
               title="Quay lại danh sách hợp đồng">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>

            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 8c-1.657 0-3 1.343-3 3v1m6-4v4m-9 5h12a2 2 0 002-2V8a2 2 0 00-2-2h-1.5A2.5 2.5 0 0014 3.5h-4A2.5 2.5 0 007.5 6H6a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">Yêu cầu hoàn cọc</h2>
                </div>
            </div>
        </div>

        <a href="{{ route('client.contracts.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-indigo-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại hợp đồng
        </a>
    </div>

    @php
        $deposit = (float) $contract->deposit_amount;
        $deduction = (float) ($contract->deposit_deduction_amount ?? 0);
        $refund = (float) ($contract->deposit_refund_amount ?? 0);
        $expectedRefund = max(0, $deposit - $deduction);
    @endphp

    {{-- INTRO --}}
    <div class="overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-blue-50 shadow-sm">
        <div class="flex items-start gap-4 p-6">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 12c0 4.142-3.358 7.5-7.5 7.5S4.5 16.142 4.5 12 7.858 4.5 12 4.5s7.5 3.358 7.5 7.5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 12v.01"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Hoàn tiền cọc</h3>
            </div>
        </div>
    </div>

    {{-- CONTRACT CARD --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-100 bg-slate-50/70 p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M7 3h8l4 4v14H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 3v5h5M9 13h6M9 17h6M9 9h2"/>
                        </svg>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Hợp đồng
                        </p>
                        <h3 class="mt-1 text-2xl font-bold text-slate-900">
                            {{ $contract->contract_code }}
                        </h3>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                            <span class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 10.5L12 3l9 7.5M5.5 9.5V21h13V9.5M9 21v-6h6v6"/>
                                </svg>
                                Phòng {{ $contract->room->room_code ?? '-' }}
                            </span>
                            <span class="text-slate-300">•</span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 19a4 4 0 00-8 0M11 11a3 3 0 100-6 3 3 0 000 6zM19 19a4 4 0 00-3-3.87M17 11a3 3 0 100-6"/>
                                </svg>
                                {{ $contract->tenant->full_name ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-3.5 py-2 text-xs font-bold
                    @if($contract->deposit_status === \App\Models\Contract::DEPOSIT_REFUND_REQUESTED)
                        bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200
                    @elseif($contract->deposit_status === \App\Models\Contract::DEPOSIT_REFUND_APPROVED)
                        bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200
                    @elseif($contract->isRefundCompleted())
                        bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200
                    @else
                        bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200
                    @endif">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $contract->deposit_status_text }}
                </span>
            </div>
        </div>

        {{-- FINANCIAL SUMMARY --}}
        <div class="grid gap-4 p-6 sm:grid-cols-3">
            <div class="rounded-2xl border border-amber-100 bg-amber-50/60 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-600">Tiền cọc</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 12c0 4.142-3.358 7.5-7.5 7.5S4.5 16.142 4.5 12 7.858 4.5 12 4.5s7.5 3.358 7.5 7.5z"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-xl font-bold text-amber-600">
                    {{ number_format($deposit, 0, ',', '.') }} VNĐ
                </p>
            </div>

            <div class="rounded-2xl border border-red-100 bg-red-50/60 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-600">Đã bù công nợ</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-100 text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-xl font-bold text-red-600">
                    {{ number_format($deduction, 0, ',', '.') }} VNĐ
                </p>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-emerald-700">Cọc còn được hoàn</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6v12m6-6H6"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-xl font-bold text-emerald-700">
                    {{ number_format($expectedRefund, 0, ',', '.') }} VNĐ
                </p>
                @if($refund > 0)
                    <p class="mt-1 text-xs text-emerald-600">
                        Số dư hoàn đã ghi nhận: {{ number_format($refund, 0, ',', '.') }} VNĐ
                    </p>
                @endif
            </div>
        </div>

        {{-- EXISTING REQUEST --}}
        @if($contract->isRefundRequested() || $contract->isRefundApproved())
            <div class="mx-6 mb-6 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 100-18 9 9 0 000 18z"/>
                </svg>
                <div>
                    <strong>Thông tin nhận tiền đã gửi:</strong>
                    <div class="mt-1">
                        {{ $contract->deposit_bank_name }} ·
                        {{ $contract->deposit_bank_account_number }} ·
                        {{ $contract->deposit_bank_account_name }}
                    </div>
                </div>
            </div>
        @endif

        {{-- REFUND COMPLETED --}}
        @if($contract->isRefundCompleted())
            <div class="mx-6 mb-6 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50">
                <div class="flex items-start gap-3 border-b border-emerald-200 bg-emerald-100/60 p-5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    <div>
                        <h4 class="font-bold text-emerald-900">Tiền hoàn đã được chuyển</h4>
                        <p class="mt-1 text-sm leading-6 text-emerald-800">Ban quản lý đã chuyển tiền. Bạn có thể xem minh chứng chuyển khoản ngay bên dưới.</p>
                    </div>
                </div>

                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div class="rounded-xl border border-emerald-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Số tiền đã chuyển</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-700">
                            {{ number_format((float) ($contract->deposit_transfer_amount ?? $contract->deposit_refund_amount ?? 0), 0, ',', '.') }} VNĐ
                        </p>
                        @if($contract->deposit_transferred_at)
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $contract->deposit_transferred_at->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-emerald-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bằng chứng chuyển khoản</p>
                        <a href="{{ route('client.deposit-refunds.proof', $contract) }}"
                           data-image-modal data-image-title="Ảnh chuyển khoản hoàn cọc"
                           class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Xem ảnh chuyển khoản
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- CONTRACT HISTORY --}}
        @if($contract->histories->isNotEmpty())
            <div class="mx-6 mb-6 rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Lịch sử xử lý</h4>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach($contract->histories->sortByDesc('created_at') as $history)
                        <div class="relative flex gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.5 2a8.5 8.5 0 11-17 0 8.5 8.5 0 0117 0z"/>
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="font-semibold text-slate-800">
                                        {{ $history->description ?: str_replace('_', ' ', ucfirst($history->action)) }}
                                    </p>
                                    <span class="text-xs text-slate-400">
                                        {{ optional($history->created_at)->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                @if($history->reason)
                                    <p class="mt-1 text-sm text-slate-500">{{ $history->reason }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- REFUND FORM --}}
        @if($contract->canRequestDepositRefund())
            <form
                action="{{ route('client.deposit-refunds.store', $contract) }}"
                method="POST"
                enctype="multipart/form-data"
                class="border-t border-slate-100 p-6"
            >
                @csrf

                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17 9V7a5 5 0 00-10 0v2M5 9h14v10H5V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 13v3"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">Thông tin nhận tiền</h4>
                        <p class="text-sm text-slate-500">Nhập chính xác thông tin nhận tiền.</p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M4 7h16M4 7l2-3h12l2 3M5 7v13h14V7"/>
                            </svg>
                            Ngân hàng *
                        </label>
                        <input name="bank_name" required
                               value="{{ old('bank_name') }}"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                               placeholder="VD: Vietcombank">
                    </div>

                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M4 7h16M6 7v13h12V7M9 4h6"/>
                            </svg>
                            Số tài khoản *
                        </label>
                        <input name="bank_account_number" required
                               value="{{ old('bank_account_number') }}"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                               placeholder="Nhập số tài khoản">
                    </div>

                    <div>
                        <label class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM5 21a7 7 0 0114 0"/>
                            </svg>
                            Tên chủ tài khoản *
                        </label>
                        <input name="bank_account_name" required
                               value="{{ old('bank_account_name') }}"
                               class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm uppercase outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                               placeholder="NGUYEN VAN A">
                    </div>
                </div>

                <div class="mt-5">
                    <label class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M4 7h16v13H4zM8 4h8M8 10h8M8 14h5"/>
                        </svg>
                        QR ngân hàng
                        <span class="font-normal text-slate-400">(không bắt buộc)</span>
                    </label>

                    <label class="mt-2 flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-4 transition hover:border-indigo-400 hover:bg-indigo-50/40">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v5m-2-2l2 2 2-2"/>
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-700">Tải ảnh QR ngân hàng</span>
                            <span class="block text-xs text-slate-400">PNG, JPG, JPEG, WEBP</span>
                        </span>
                        <input type="file" name="qr_image"
                               accept="image/png,image/jpeg,image/webp"
                               class="hidden">
                    </label>
                </div>

                <div class="mt-5">
                    <label class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 10h8M8 14h5M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                        </svg>
                        Ghi chú
                    </label>
                    <textarea name="note" rows="4"
                              class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                              placeholder="Ví dụ: Vui lòng chuyển tiền vào tài khoản trên.">{{ old('note') }}</textarea>
                </div>

                <div class="mt-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 9v4m0 4h.01M10.29 3.86l-8.18 14A2 2 0 003.84 21h16.32a2 2 0 001.73-3.14l-8.18-14a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div>
                        <strong>Lưu ý:</strong>
                        Tiền cọc đã được tự động bù vào công nợ. Quản trị viên chỉ chuyển
                        phần cọc còn dư sau khi kiểm tra các khoản khấu trừ hợp lệ.
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('client.contracts.index') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Quay lại
                    </a>

                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 10l9-7 9 7v10a2 2 0 01-2 2H5a2 2 0 01-2-2V10z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 13v6m-3-3h6"/>
                        </svg>
                        Gửi yêu cầu hoàn cọc
                    </button>
                </div>
            </form>
        @else
            <div class="border-t border-slate-100 p-6">
                <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 100-18 9 9 0 000 18z"/>
                    </svg>
                    <div>
                        <strong class="text-slate-800">Chưa thể gửi yêu cầu hoàn cọc.</strong>
                        <p class="mt-1">Hợp đồng chưa ở trạng thái phù hợp để yêu cầu hoàn cọc hoặc yêu cầu đã được gửi trước đó.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
