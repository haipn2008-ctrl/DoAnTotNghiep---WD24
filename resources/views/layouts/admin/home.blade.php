@extends('layouts.admin.index')

@section('title', 'Bảng điều khiển | Quản lý phòng trọ')
@section('page_title', 'Bảng điều khiển quản lý phòng trọ')


@php
    $roomTotal = max((int) ($stats['total_rooms'] ?? 0), 1);
    $fillRate = round((($stats['occupied_rooms'] ?? 0) / $roomTotal) * 100);

    $statusLabels = [
        'unpaid' => ['label' => 'Chưa thanh toán', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'partial' => ['label' => 'Thanh toán một phần', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'paid' => ['label' => 'Đã thanh toán', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'pending' => ['label' => 'Chờ xử lý', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'draft' => ['label' => 'Bản nháp', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'],
        'pending_signature' => ['label' => 'Chờ ký', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'pending_deposit' => ['label' => 'Chờ cọc', 'class' => 'bg-orange-50 text-orange-700 ring-orange-200'],
        'awaiting_move_in' => ['label' => 'Chờ nhận phòng', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
        'active' => ['label' => 'Đang hiệu lực', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'expired' => ['label' => 'Hết hạn - chờ xử lý', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'terminated' => ['label' => 'Đã kết thúc', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'settling' => ['label' => 'Quyết toán', 'class' => 'bg-violet-50 text-violet-700 ring-violet-200'],
        'completed' => ['label' => 'Hoàn tất', 'class' => 'bg-green-50 text-green-700 ring-green-200'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-gray-50 text-gray-700 ring-gray-200'],
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-medium text-slate-500">Tổng quan vận hành hôm nay</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Xin chào, {{ Auth::user()->name ?? 'Quản trị viên' }}</h2>
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

        {{-- Cảnh báo vận hành --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-950">Cảnh báo vận hành</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <a href="{{ route('admin.contracts.index', ['status' => 'active']) }}"
                   class="flex items-start gap-3 rounded-lg border p-4 transition hover:shadow-md
                          {{ $expiringContracts->count() > 0 ? 'border-orange-200 bg-orange-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="mt-0.5 rounded-full p-2 {{ $expiringContracts->count() > 0 ? 'bg-orange-100 text-orange-600' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Sắp hết hạn</p>
                        <p class="mt-1 text-2xl font-bold {{ $expiringContracts->count() > 0 ? 'text-orange-700' : 'text-slate-400' }}">{{ $expiringContracts->count() }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">Hợp đồng trong 1 tháng tới</p>
                    </div>
                </a>

                <a href="{{ route('admin.invoices.index', ['status' => 'unpaid']) }}"
                   class="flex items-start gap-3 rounded-lg border p-4 transition hover:shadow-md
                          {{ ($overdueInvoices->count ?? 0) > 0 ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="mt-0.5 rounded-full p-2 {{ ($overdueInvoices->count ?? 0) > 0 ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Hóa đơn quá hạn</p>
                        <p class="mt-1 text-2xl font-bold {{ ($overdueInvoices->count ?? 0) > 0 ? 'text-red-700' : 'text-slate-400' }}">{{ $overdueInvoices->count ?? 0 }}</p>
                        @if(($overdueInvoices->total_amount ?? 0) > 0)
                            <p class="mt-0.5 text-xs font-medium text-red-600">{{ number_format($overdueInvoices->total_amount, 0, ',', '.') }}đ</p>
                        @else
                            <p class="mt-0.5 text-xs text-slate-500">Không có</p>
                        @endif
                    </div>
                </a>

                <a href="{{ route('admin.support.index', ['status' => 'new']) }}"
                   class="flex items-start gap-3 rounded-lg border p-4 transition hover:shadow-md
                          {{ $pendingSupportCount > 0 ? 'border-yellow-200 bg-yellow-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="mt-0.5 rounded-full p-2 {{ $pendingSupportCount > 0 ? 'bg-yellow-100 text-yellow-600' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Hỗ trợ chờ xử lý</p>
                        <p class="mt-1 text-2xl font-bold {{ $pendingSupportCount > 0 ? 'text-yellow-700' : 'text-slate-400' }}">{{ $pendingSupportCount }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">Yêu cầu chưa phản hồi</p>
                    </div>
                </a>

                <a href="{{ route('admin.contracts.index') }}"
                   class="flex items-start gap-3 rounded-lg border p-4 transition hover:shadow-md
                          {{ ($pendingExtensionCount + $pendingTerminationCount) > 0 ? 'border-purple-200 bg-purple-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="mt-0.5 rounded-full p-2 {{ ($pendingExtensionCount + $pendingTerminationCount) > 0 ? 'bg-purple-100 text-purple-600' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Yêu cầu hợp đồng</p>
                        <p class="mt-1 text-2xl font-bold {{ ($pendingExtensionCount + $pendingTerminationCount) > 0 ? 'text-purple-700' : 'text-slate-400' }}">{{ $pendingExtensionCount + $pendingTerminationCount }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">Gia hạn {{ $pendingExtensionCount }} · Chấm dứt {{ $pendingTerminationCount }}</p>
                    </div>
                </a>
            </div>

            @if($expiringContracts->count() > 0)
                <div class="mt-4 overflow-hidden rounded-lg border border-orange-200">
                    <table class="min-w-full divide-y divide-orange-100 text-sm">
                        <thead class="bg-orange-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-orange-700">Mã hợp đồng</th>
                                <th class="px-4 py-2 text-left font-medium text-orange-700">Phòng</th>
                                <th class="px-4 py-2 text-left font-medium text-orange-700">Ngày hết hạn</th>
                                <th class="px-4 py-2 text-left font-medium text-orange-700">Còn lại</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-orange-50 bg-white">
                            @foreach($expiringContracts as $c)
                                @php $days = today()->diffInDays($c->end_date, false); @endphp
                                <tr>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('admin.contracts.show', $c) }}" class="font-medium text-indigo-600 hover:underline">{{ $c->contract_code }}</a>
                                    </td>
                                    <td class="px-4 py-2 text-slate-700">{{ $c->room?->room_code ?? '—' }}</td>
                                    <td class="px-4 py-2 text-slate-700">{{ $c->end_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $days <= 7 ? 'bg-red-100 text-red-700' : ($days <= 14 ? 'bg-orange-100 text-orange-700' : 'bg-yellow-100 text-yellow-700') }}">
                                            {{ $days <= 0 ? 'Hôm nay' : $days.' ngày' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-950">Hóa đơn gần đây</h3>
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
                                @php($invoiceStatus = $statusLabels[$invoice->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                                <tr>
                                    <td class="px-5 py-4 font-medium text-slate-900">{{ $invoice->room->room_code ?? 'Không có' }}</td>
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
                    </div>
                    <a href="{{ route('admin.contracts.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Xem tất cả</a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($recentContracts as $contract)
                        @php($contractStatus = $statusLabels[$contract->status] ?? ['label' => 'Không xác định', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'])
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-950">{{ $contract->contract_code }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $contract->tenant->full_name ?? 'Chưa có khách' }} · Phòng {{ $contract->room->room_code ?? 'Không có' }}
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
