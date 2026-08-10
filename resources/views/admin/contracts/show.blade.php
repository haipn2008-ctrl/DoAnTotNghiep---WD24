@extends('layouts.admin.index')

@section('title', 'Chi tiết hợp đồng | Quản lý phòng trọ')
@section('page_title', 'Chi tiết hợp đồng')

@php
    $contract->loadMissing(['room', 'tenant']);
    $statusOptions = [
        'pending' => ['label' => 'Chờ khách ký', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'],
        'active' => ['label' => 'Đang thuê', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'terminated' => ['label' => 'Đã kết thúc', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200', 'dot' => 'bg-rose-500'],
        'expired' => ['label' => 'Hết hạn', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200', 'dot' => 'bg-amber-500'],
    ];
    $status = $statusOptions[$contract->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200', 'dot' => 'bg-slate-400'];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Hợp đồng {{ $contract->contract_code ?: 'HD' . str_pad($contract->id, 3, '0', STR_PAD_LEFT) }}</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Chi tiết hợp đồng</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.contracts.edit', $contract) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-edit text-lg"></i>
                    Cập nhật hợp đồng
                </a>
                <a href="{{ route('admin.contracts.print', $contract->id) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-100">
                    <i class="bx bx-printer text-lg"></i>
                    In hợp đồng
                </a>
                <a href="{{ route('admin.contracts.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-arrow-back text-lg"></i>
                    Quay lại
                </a>
            </div>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
                <div>
                    <h3 class="font-semibold text-slate-950">{{ $contract->contract_code ?: 'HD' . str_pad($contract->id, 3, '0', STR_PAD_LEFT) }}</h3>
                    <p class="text-sm text-slate-500">Từ {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</p>
                </div>
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $status['class'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $status['dot'] }}"></span>
                    {{ $status['label'] }}
                </span>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">Tiền thuê/tháng</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ number_format($contract->monthly_rent ?? $contract->room->price ?? 0, 0, ',', '.') }}đ</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">Tiền cọc</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ number_format($contract->deposit_amount ?? 0, 0, ',', '.') }}đ</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">Số người</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ $contract->number_of_people ?? 1 }} người</p>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Thông tin hợp đồng</h3></div>
                <dl class="divide-y divide-slate-100 px-5 text-sm">
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Ngày ký</dt><dd class="font-semibold text-slate-950">{{ $contract->signed_at?->format('d/m/Y') ?? 'Chưa cập nhật' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Trạng thái tiền cọc</dt><dd class="font-semibold text-slate-950">{{ ['pending' => 'Chưa thu', 'paid' => 'Đã thu', 'returned' => 'Đã hoàn trả'][$contract->deposit_status] ?? 'Chưa xác định' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Ngày thu tiền cọc</dt><dd class="font-semibold text-slate-950">{{ $contract->deposit_paid_at?->format('d/m/Y H:i') ?? 'Chưa thu' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Ngày kết thúc thực tế</dt><dd class="font-semibold text-slate-950">{{ $contract->actual_end_date?->format('d/m/Y') ?? 'Chưa kết thúc' }}</dd></div>
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Tệp hợp đồng</dt><dd>@if($contract->contractFileExists())<a href="{{ route('admin.contracts.file', $contract) }}" class="font-semibold text-indigo-700">Xem tệp</a>@else<span class="text-slate-400">Chưa đính kèm</span>@endif</dd></div>
                </dl>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Dịch vụ đăng ký</h3></div>
                <div class="space-y-3 p-5 text-sm">
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 p-3"><span>Internet</span><span class="font-semibold {{ $contract->internet_enabled ? 'text-emerald-700' : 'text-slate-400' }}">{{ $contract->internet_enabled ? number_format($setting->internet_fee, 0, ',', '.').'đ/tháng' : 'Không đăng ký' }}</span></div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 p-3"><span>Dịch vụ chung</span><span class="font-semibold {{ $contract->service_enabled ? 'text-emerald-700' : 'text-slate-400' }}">{{ $contract->service_enabled ? number_format($setting->service_fee, 0, ',', '.').'đ/tháng' : 'Không đăng ký' }}</span></div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 p-3"><span>Gửi xe</span><span class="font-semibold {{ $contract->parking_quantity > 0 ? 'text-emerald-700' : 'text-slate-400' }}">{{ $contract->parking_quantity > 0 ? $contract->parking_quantity.' xe · '.number_format($setting->parking_fee * $contract->parking_quantity, 0, ',', '.').'đ/tháng' : 'Không đăng ký' }}</span></div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Tình hình tài chính</h3></div>
                <div class="grid grid-cols-2 gap-3 p-5 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-slate-500">Số hóa đơn</p><p class="mt-1 text-lg font-bold">{{ $contract->invoices->count() }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-slate-500">Tổng đã lập</p><p class="mt-1 font-bold">{{ number_format($totalInvoiced, 0, ',', '.') }}đ</p></div>
                    <div class="rounded-lg bg-emerald-50 p-3"><p class="text-emerald-700">Đã thanh toán</p><p class="mt-1 font-bold text-emerald-800">{{ number_format($totalPaid, 0, ',', '.') }}đ</p></div>
                    <div class="rounded-lg bg-rose-50 p-3"><p class="text-rose-700">Còn phải thu</p><p class="mt-1 font-bold text-rose-800">{{ number_format($totalOutstanding, 0, ',', '.') }}đ</p></div>
                    <a href="{{ route('admin.invoices.index', ['keyword' => $contract->contract_code]) }}" class="col-span-2 text-center font-semibold text-indigo-700">Xem các hóa đơn liên quan →</a>
                </div>
            </section>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Bàn giao và chỉ số điện nước</h3></div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                <div class="rounded-lg bg-indigo-50 p-4"><p class="text-sm font-semibold text-indigo-800">Chỉ số bàn giao</p><p class="mt-2 text-sm text-slate-600">Ngày {{ $handoverReading?->record_date?->format('d/m/Y') ?? 'chưa ghi nhận' }}</p><div class="mt-3 flex gap-5"><span>Điện: <strong>{{ $handoverReading?->electricity_new ?? '—' }}</strong> kWh</span><span>Nước: <strong>{{ $handoverReading?->water_new ?? '—' }}</strong> m³</span></div></div>
                <div class="rounded-lg bg-sky-50 p-4"><p class="text-sm font-semibold text-sky-800">Lần ghi gần nhất</p><p class="mt-2 text-sm text-slate-600">Ngày {{ $latestReading?->record_date?->format('d/m/Y') ?? 'chưa ghi nhận' }}</p><div class="mt-3 flex gap-5"><span>Điện: <strong>{{ $latestReading?->electricity_new ?? '—' }}</strong> kWh</span><span>Nước: <strong>{{ $latestReading?->water_new ?? '—' }}</strong> m³</span></div></div>
                <div class="rounded-lg bg-amber-50 p-4"><p class="text-sm font-semibold text-amber-800">Chỉ số trả phòng</p><p class="mt-2 text-sm text-slate-600">Ngày {{ $checkoutReading?->record_date?->format('d/m/Y') ?? 'chưa trả phòng' }}</p><div class="mt-3 flex gap-5"><span>Điện: <strong>{{ $checkoutReading?->electricity_new ?? '—' }}</strong> kWh</span><span>Nước: <strong>{{ $checkoutReading?->water_new ?? '—' }}</strong> m³</span></div></div>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin phòng</h3>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Mã phòng</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $contract->room->room_code ?? '---' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Tầng</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $contract->room->floor ?? '---' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Diện tích</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $contract->room->area ?? '---' }} m²</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Giá phòng</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ number_format($contract->room->price ?? 0, 0, ',', '.') }}đ</p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-950">Thông tin người thuê</h3>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Họ tên</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $contract->tenant->full_name ?? '---' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">CCCD</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $contract->tenant->cccd ?? '---' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Số điện thoại</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $contract->tenant->phone ?? '---' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-500">Email</p>
                        <p class="mt-2 break-words font-semibold text-slate-950">{{ $contract->tenant->email ?? '---' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4 sm:col-span-2">
                        <p class="text-sm font-medium text-slate-500">Địa chỉ</p>
                        <p class="mt-2 font-semibold text-slate-950">{{ $contract->tenant->address ?? '---' }}</p>
                    </div>
                </div>
            </section>
        </div>

        @if($contract->note || $contract->termination_reason || $contract->termination_note || $contract->extend_reason || $contract->extend_note)
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-950">Ghi chú và lịch sử hợp đồng</h3></div>
                <dl class="grid gap-4 p-5 text-sm md:grid-cols-2">
                    @if($contract->note)<div><dt class="font-semibold text-slate-700">Ghi chú hợp đồng</dt><dd class="mt-1 whitespace-pre-line text-slate-600">{{ $contract->note }}</dd></div>@endif
                    @if($contract->extend_reason || $contract->extend_note)<div><dt class="font-semibold text-slate-700">Gia hạn</dt><dd class="mt-1 text-slate-600">{{ $contract->extend_reason }} {{ $contract->extend_note }}</dd></div>@endif
                    @if($contract->termination_reason || $contract->termination_note)<div><dt class="font-semibold text-slate-700">Kết thúc hợp đồng</dt><dd class="mt-1 text-slate-600">{{ ['expired' => 'Hết hạn hợp đồng', 'early' => 'Trả phòng trước hạn', 'violation' => 'Vi phạm hợp đồng', 'other' => 'Lý do khác'][$contract->termination_reason] ?? $contract->termination_reason }} {{ $contract->termination_note }}</dd></div>@endif
                </dl>
            </section>
        @endif
    </div>
@endsection
