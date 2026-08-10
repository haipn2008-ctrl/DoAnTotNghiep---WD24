@extends('layouts.client.index')

@section('title', 'Chi tiết hợp đồng | Cổng khách thuê')
@section('page_title', 'Chi tiết hợp đồng')

@php
    $statuses = ['pending' => 'Chờ ký', 'active' => 'Đang hiệu lực', 'expired' => 'Hết hạn', 'terminated' => 'Đã kết thúc'];
    $depositStatuses = ['pending' => 'Chưa thu', 'paid' => 'Đã thu', 'returned' => 'Đã hoàn trả'];
    $effectiveEnd = $contract->extend_end_date ?? $contract->end_date;
@endphp

@section('content')
    <div class="space-y-5">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><a href="{{ route('client.contracts.index') }}" class="text-sm font-semibold text-indigo-700">← Hợp đồng của tôi</a><h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $contract->contract_code }}</h2><p class="mt-1 text-sm text-slate-500">{{ $statuses[$contract->status] ?? 'Không xác định' }}</p></div>@if($contract->contractFileExists())<a href="{{ route('client.contracts.file', $contract) }}" target="_blank" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Xem file hợp đồng</a>@elseif($contract->contract_file)<span class="text-sm font-medium text-amber-700">File hợp đồng không còn tồn tại</span>@endif</div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Phòng</p><p class="mt-2 text-xl font-bold">{{ $contract->room->room_code ?? '-' }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tiền thuê/tháng</p><p class="mt-2 text-xl font-bold">{{ number_format($contract->monthly_rent, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tiền cọc</p><p class="mt-2 text-xl font-bold">{{ number_format($contract->deposit_amount, 0, ',', '.') }}đ</p><p class="mt-1 text-xs text-slate-500">{{ $depositStatuses[$contract->deposit_status] ?? 'Chưa cập nhật' }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Số người</p><p class="mt-2 text-xl font-bold">{{ $contract->number_of_people }} người</p></div>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold text-slate-950">Thời hạn hợp đồng</h3><div class="mt-4 grid gap-4 sm:grid-cols-3"><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày bắt đầu</p><p class="mt-1 font-bold">{{ $contract->start_date->format('d/m/Y') }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày kết thúc</p><p class="mt-1 font-bold">{{ $effectiveEnd->format('d/m/Y') }}</p></div><div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Ngày ký</p><p class="mt-1 font-bold">{{ $contract->signed_at?->format('d/m/Y') ?? 'Chưa cập nhật' }}</p></div></div>@if($contract->extended_at)<p class="mt-4 text-sm text-slate-600">Hợp đồng đã được gia hạn ngày {{ $contract->extended_at->format('d/m/Y') }}.</p>@endif</section>

        @if($contract->note)<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">Ghi chú hợp đồng</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $contract->note }}</p></section>@endif
    </div>
@endsection
