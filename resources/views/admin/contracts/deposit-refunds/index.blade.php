@extends('layouts.admin.index')

@section('title', 'Yêu cầu hoàn cọc')
@section('page_title', 'Quản lý phòng trọ')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Yêu cầu hoàn cọc
            </h1>

        </div>

        <a href="{{ route('admin.contracts.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="bx bx-arrow-back text-lg"></i>
            Quản lý hợp đồng
        </a>
    </div>

    {{-- THỐNG KÊ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Tổng yêu cầu</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $contracts->count() }}
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600">
                    <i class="bx bx-wallet"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-700">Chờ duyệt</p>
                    <p class="mt-2 text-3xl font-bold text-amber-700">
                        {{ $contracts->filter(fn($contract) => $contract->isRefundRequested())->count() }}
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-xl text-amber-700">
                    <i class="bx bx-time-five"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-700">Đã duyệt</p>
                    <p class="mt-2 text-3xl font-bold text-blue-700">
                        {{ $contracts->filter(fn($contract) => $contract->isRefundApproved())->count() }}
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-xl text-blue-700">
                    <i class="bx bx-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Đã hoàn tất</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">
                        {{ $contracts->filter(fn($contract) => $contract->isRefundCompleted())->count() }}
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-xl text-emerald-700">
                    <i class="bx bx-check-double"></i>
                </div>
            </div>
        </div>

    </div>

    {{-- DANH SÁCH --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Danh sách yêu cầu
                </h2>
            </div>

            <div class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600">
                {{ $contracts->count() }} yêu cầu
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Hợp đồng</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Khách thuê</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Phòng</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tiền cọc</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Thông tin nhận tiền</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Thao tác</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">

                @forelse($contracts as $contract)

                    <tr class="transition hover:bg-slate-50/80">

                        <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-500">
                            {{ $loop->iteration }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-lg text-indigo-600">
                                    <i class="bx bx-file"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">
                                        {{ $contract->contract_code }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        Hợp đồng đã chấm dứt
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $contract->tenant->full_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $contract->tenant->phone ?? 'Chưa có SĐT' }}
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            <span class="font-semibold text-indigo-600">
                                {{ $contract->room->room_code ?? '-' }}
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            <div class="font-bold text-amber-600">
                                {{ number_format($contract->deposit_amount, 0, ',', '.') }} VNĐ
                            </div>

                            @if($contract->deposit_refund_amount !== null)
                                <div class="mt-1 text-xs text-emerald-600">
                                    Hoàn: {{ number_format($contract->deposit_refund_amount, 0, ',', '.') }} VNĐ
                                </div>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="space-y-1 text-sm text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i class="bx bx-building-house text-slate-400"></i>
                                    <span>{{ $contract->deposit_bank_name ?? 'Chưa có ngân hàng' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="bx bx-credit-card text-slate-400"></i>
                                    <span>{{ $contract->deposit_bank_account_number ?? 'Chưa có STK' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="bx bx-user text-slate-400"></i>
                                    <span class="font-medium">{{ $contract->deposit_bank_account_name ?? 'Chưa có chủ TK' }}</span>
                                </div>

                                @if($contract->deposit_qr_image)
                                    <a href="{{ route('admin.deposit-refunds.qr', $contract) }}"
                                       data-image-modal data-image-title="Mã QR hoàn cọc"
                                       class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                        <i class="bx bx-qr-scan text-base"></i>
                                        Xem QR
                                    </a>
                                @endif
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">

                            @if($contract->isRefundRequested())

                                <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    <i class="bx bx-time-five"></i>
                                    Chờ duyệt
                                </span>

                            @elseif($contract->isRefundApproved())

                                <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    <i class="bx bx-check-circle"></i>
                                    Đã duyệt
                                </span>

                            @elseif($contract->isAwaitingRefundReceiptConfirmation())

                                <span class="inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">
                                    <i class="bx bx-user-check"></i>
                                    Chờ khách xác nhận
                                </span>

                            @elseif($contract->deposit_status === \App\Models\Contract::DEPOSIT_REFUND_REJECTED)

                                <span class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                    <i class="bx bx-x-circle"></i>
                                    Từ chối
                                </span>

                            @elseif($contract->isRefundCompleted())

                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    <i class="bx bx-check-double"></i>
                                    Đã hoàn tất
                                </span>

                            @else

                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                    <i class="bx bx-minus-circle"></i>
                                    Chưa xử lý
                                </span>

                            @endif

                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-center">

                            @if($contract->isRefundRequested())

                                <div class="flex items-center justify-center gap-2">

                                    {{-- DUYỆT --}}
                                    <button
                                        type="button"
                                        data-refund-open="refundModal{{ $contract->id }}"
                                        onclick="openRefundModal('refundModal{{ $contract->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100"
                                    >
                                        <i class="bx bx-check-circle text-lg"></i>
                                        Duyệt
                                    </button>

                                    {{-- TỪ CHỐI --}}
                                    <button
                                        type="button"
                                        data-refund-open="rejectRefundModal{{ $contract->id }}"
                                        onclick="openRefundModal('rejectRefundModal{{ $contract->id }}')"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100"
                                    >
                                        <i class="bx bx-x-circle text-lg"></i>
                                        Từ chối
                                    </button>

                                </div>

                            @elseif($contract->isRefundApproved())

                                <button
                                    type="button"
                                    data-refund-open="refundModal{{ $contract->id }}"
                                    onclick="openRefundModal('refundModal{{ $contract->id }}')"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                >
                                    <i class="bx bx-transfer text-lg"></i>
                                    Xác nhận chuyển
                                </button>

                            @elseif($contract->isAwaitingRefundReceiptConfirmation())

                                @if($contract->deposit_receipt_confirmation_due_at?->lessThanOrEqualTo(now()))
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700">
                                        <i class="bx bx-error-circle text-lg"></i>
                                        Quá hạn xác nhận
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-semibold text-violet-700">
                                        <i class="bx bx-time-five text-lg"></i>
                                        Chờ đến {{ $contract->deposit_receipt_confirmation_due_at?->format('H:i d/m/Y') }}
                                    </span>
                                @endif

                            @elseif($contract->isRefundCompleted()
                                && $contract->deposit_receipt_confirmation_source === \App\Services\DepositRefundReceiptService::SOURCE_AUTOMATIC)

                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700">
                                    <i class="bx bx-error-circle text-lg"></i>
                                    Quá hạn xác nhận
                                </span>

                            @else

                                <span class="text-sm font-medium text-slate-400">
                                    Đã xử lý
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">

                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                <i class="bx bx-wallet"></i>
                            </div>

                            <h3 class="mt-4 font-semibold text-slate-900">
                                Chưa có yêu cầu hoàn cọc
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Khi khách thuê gửi yêu cầu hoàn cọc, yêu cầu sẽ xuất hiện tại đây.
                            </p>

                        </td>
                    </tr>

                @endforelse

                </tbody>
            </table>
        </div>


        {{-- FORM XỬ LÝ: tách riêng để index gọn và tránh lỗi modal/layout --}}
        @include('admin.contracts.deposit-refunds._refund-modals')

        <div class="border-t border-slate-200 bg-slate-50/60 px-5 py-3 text-sm text-slate-500 sm:px-6">
            Hiển thị
            <span class="font-semibold text-slate-700">{{ $contracts->count() }}</span>
            yêu cầu
        </div>

    </div>

</div>


@endsection
