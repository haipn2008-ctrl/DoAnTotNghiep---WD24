@extends('layouts.admin.index')
<<<<<<< HEAD
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
=======

@section('title', 'Dashboard | Quản lý phòng trọ')
@section('page_title', 'Dashboard quản lý phòng trọ')

@php
    $roomTotal = max((int) ($stats['total_rooms'] ?? 0), 1);
    $fillRate = round((($stats['occupied_rooms'] ?? 0) / $roomTotal) * 100);

    $statusLabels = [
        'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'partial' => ['label' => 'Thanh toán một phần', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'active' => ['label' => 'Đang hiệu lực', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'expired' => ['label' => 'Hết hạn', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'],
        'terminated' => ['label' => 'Đã kết thúc', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
    ];
@endphp

>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Tổng quan vận hành hôm nay</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Xin chào, {{ Auth::user()->name ?? 'Admin' }}</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.rooms.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    <i class="bx bx-plus text-lg"></i>
                    Thêm phòng
                </a>
                <a href="{{ route('admin.invoices.generate') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i class="bx bx-receipt text-lg"></i>
                    Sinh hóa đơn
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Tổng số phòng</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <i class="bx bx-building-house text-xl"></i>
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold text-slate-950">{{ number_format($stats['total_rooms'] ?? 0) }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $stats['available_rooms'] ?? 0 }} phòng trống, {{ $stats['maintenance_rooms'] ?? 0 }} bảo trì</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Tỷ lệ lấp đầy</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <i class="bx bx-line-chart text-xl"></i>
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold text-slate-950">{{ $fillRate }}%</p>
                <div class="mt-3 h-2 rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min($fillRate, 100) }}%"></div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Khách thuê</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                        <i class="bx bx-user text-xl"></i>
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold text-slate-950">{{ number_format($stats['total_tenants'] ?? 0) }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ number_format($stats['active_contracts'] ?? 0) }} hợp đồng đang hiệu lực</p>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500">Doanh thu tháng</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <i class="bx bx-wallet text-xl"></i>
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold text-slate-950">{{ number_format($stats['monthly_revenue'] ?? 0, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-sm text-slate-500">{{ number_format($stats['unpaid_invoices'] ?? 0) }} hóa đơn cần thu</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-950">Hóa đơn gần đây</h3>
                        <p class="text-sm text-slate-500">Theo dõi nhanh công nợ mới phát sinh</p>
                    </div>
                    <a href="{{ route('admin.invoices.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Xem tất cả</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Phòng</th>
                                <th class="px-5 py-3">Kỳ</th>
                                <th class="px-5 py-3">Tổng tiền</th>
                                <th class="px-5 py-3">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentInvoices as $invoice)
                                @php($invoiceStatus = $statusLabels[$invoice->status] ?? ['label' => $invoice->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                                <tr>
                                    <td class="px-5 py-4 font-medium text-slate-900">{{ $invoice->room->room_code ?? 'N/A' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $invoice->month }}/{{ $invoice->year }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $invoiceStatus['class'] }}">{{ $invoiceStatus['label'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-slate-500">Chưa có hóa đơn nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-950">Hợp đồng mới</h3>
                        <p class="text-sm text-slate-500">Các hợp đồng được cập nhật gần nhất</p>
                    </div>
                    <a href="{{ route('admin.contracts.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Xem tất cả</a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($recentContracts as $contract)
                        @php($contractStatus = $statusLabels[$contract->status] ?? ['label' => $contract->status, 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-950">{{ $contract->contract_code }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $contract->tenant->full_name ?? 'Chưa có khách' }} · Phòng {{ $contract->room->room_code ?? 'N/A' }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $contractStatus['class'] }}">{{ $contractStatus['label'] }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-8 text-center text-sm text-slate-500">Chưa có hợp đồng nào.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
