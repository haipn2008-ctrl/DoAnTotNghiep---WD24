@extends('layouts.admin.index')

@section('title', 'Tổng quan | Quản lý phòng trọ')
@section('page_title', 'Tổng quan')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Báo cáo vận hành</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Tổng quan hệ thống</h2>
        </div>

        {{-- KPI Cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Doanh thu tháng này</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700">{{ number_format($monthRevenue, 0, ',', '.') }}đ</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Tỷ lệ thu hồi</p>
                <p class="mt-3 text-3xl font-bold text-indigo-700">{{ $collectionRate }}%</p>
                <p class="mt-1 text-xs text-slate-400">Trên tổng đã xuất hóa đơn</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Hợp đồng hoạt động</p>
                <p class="mt-3 text-3xl font-bold text-sky-700">{{ $activeContracts }}</p>
                <p class="mt-1 text-xs text-slate-400">/ {{ $totalRooms }} phòng</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Tổng tiền công nợ</p>
                <p class="mt-3 text-3xl font-bold text-amber-700">{{ number_format($totalReceivable, 0, ',', '.') }}đ</p>
                <p class="mt-1 text-xs text-slate-400">Chưa thu được</p>
            </div>
        </div>

        {{-- Charts --}}
        <div class="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Doanh thu theo tháng</h3>
                <p class="mt-1 text-sm text-slate-500">So sánh năm {{ $previousYear }} và {{ $currentYear }}</p>
                <div id="monthly-revenue-chart" class="mt-4 min-h-[350px]"></div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Trạng thái phòng</h3>
                <div id="room-status-chart" class="mt-4 min-h-[350px]"></div>
            </section>
        </div>

        {{-- Cảnh báo vận hành --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-950">Cảnh báo vận hành</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                {{-- Hợp đồng sắp hết hạn --}}
                <a href="{{ route('admin.contracts.index', ['status' => 'active']) }}"
                   class="group flex items-start gap-3 rounded-lg border p-4 transition hover:shadow-md
                          {{ $expiringContracts->count() > 0 ? 'border-orange-200 bg-orange-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="mt-0.5 rounded-full p-2 {{ $expiringContracts->count() > 0 ? 'bg-orange-100 text-orange-600' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Sắp hết hạn</p>
                        <p class="mt-1 text-2xl font-bold {{ $expiringContracts->count() > 0 ? 'text-orange-700' : 'text-slate-400' }}">
                            {{ $expiringContracts->count() }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">Hợp đồng trong 1 tháng tới</p>
                    </div>
                </a>

                {{-- Hóa đơn quá hạn --}}
                <a href="{{ route('admin.invoices.index', ['status' => 'unpaid']) }}"
                   class="group flex items-start gap-3 rounded-lg border p-4 transition hover:shadow-md
                          {{ ($overdueInvoices->count ?? 0) > 0 ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="mt-0.5 rounded-full p-2 {{ ($overdueInvoices->count ?? 0) > 0 ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Hóa đơn quá hạn</p>
                        <p class="mt-1 text-2xl font-bold {{ ($overdueInvoices->count ?? 0) > 0 ? 'text-red-700' : 'text-slate-400' }}">
                            {{ $overdueInvoices->count ?? 0 }}
                        </p>
                        @if(($overdueInvoices->total_amount ?? 0) > 0)
                            <p class="mt-0.5 text-xs text-red-600 font-medium">{{ number_format($overdueInvoices->total_amount, 0, ',', '.') }}đ</p>
                        @else
                            <p class="mt-0.5 text-xs text-slate-500">Không có</p>
                        @endif
                    </div>
                </a>

                {{-- Yêu cầu hỗ trợ --}}
                <a href="{{ route('admin.support.index', ['status' => 'new']) }}"
                   class="group flex items-start gap-3 rounded-lg border p-4 transition hover:shadow-md
                          {{ $pendingSupportCount > 0 ? 'border-yellow-200 bg-yellow-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="mt-0.5 rounded-full p-2 {{ $pendingSupportCount > 0 ? 'bg-yellow-100 text-yellow-600' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Hỗ trợ chờ xử lý</p>
                        <p class="mt-1 text-2xl font-bold {{ $pendingSupportCount > 0 ? 'text-yellow-700' : 'text-slate-400' }}">
                            {{ $pendingSupportCount }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">Yêu cầu chưa phản hồi</p>
                    </div>
                </a>

                {{-- Yêu cầu gia hạn / chấm dứt --}}
                <a href="{{ route('admin.contracts.index') }}"
                   class="group flex items-start gap-3 rounded-lg border p-4 transition hover:shadow-md
                          {{ ($pendingExtensionCount + $pendingTerminationCount) > 0 ? 'border-purple-200 bg-purple-50' : 'border-slate-200 bg-slate-50' }}">
                    <div class="mt-0.5 rounded-full p-2 {{ ($pendingExtensionCount + $pendingTerminationCount) > 0 ? 'bg-purple-100 text-purple-600' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">Yêu cầu hợp đồng</p>
                        <p class="mt-1 text-2xl font-bold {{ ($pendingExtensionCount + $pendingTerminationCount) > 0 ? 'text-purple-700' : 'text-slate-400' }}">
                            {{ $pendingExtensionCount + $pendingTerminationCount }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">Gia hạn {{ $pendingExtensionCount }} · Chấm dứt {{ $pendingTerminationCount }}</p>
                    </div>
                </a>
            </div>

            {{-- Danh sách hợp đồng sắp hết hạn --}}
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
                                <tr>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('admin.contracts.show', $c) }}" class="font-medium text-indigo-600 hover:underline">{{ $c->contract_code }}</a>
                                    </td>
                                    <td class="px-4 py-2 text-slate-700">{{ $c->room?->room_code ?? '—' }}</td>
                                    <td class="px-4 py-2 text-slate-700">{{ $c->end_date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2">
                                        @php $days = today()->diffInDays($c->end_date, false); @endphp
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

        {{-- Thống kê nhanh --}}
        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Thống kê nhanh</h3>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Doanh thu hôm nay</p><p class="mt-2 text-xl font-bold text-slate-950">{{ number_format($todayRevenue, 0, ',', '.') }}đ</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Doanh thu tháng này</p><p class="mt-2 text-xl font-bold text-slate-950">{{ number_format($monthRevenue, 0, ',', '.') }}đ</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Đã thuê</p><p class="mt-2 text-xl font-bold text-emerald-700">{{ $occupiedRooms }} phòng · {{ $occupiedPercent }}%</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-sm text-slate-500">Còn trống</p><p class="mt-2 text-xl font-bold text-sky-700">{{ $availableRooms }} phòng · {{ $availablePercent }}%</p></div>
                    <div class="rounded-lg bg-slate-50 p-4 sm:col-span-2"><p class="text-sm text-slate-500">Bảo trì</p><p class="mt-2 text-xl font-bold text-amber-700">{{ $maintenanceRooms }} phòng · {{ $maintenancePercent }}%</p></div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        new ApexCharts(document.querySelector("#monthly-revenue-chart"), {
            chart: { type: 'bar', height: 350, toolbar: { show: false } },
            series: [
                { name: 'Doanh Thu {{ $previousYear }}', data: @json($monthlyRevenuePreviousYear) },
                { name: 'Doanh Thu {{ $currentYear }}', data: @json($monthlyRevenueCurrentYear) }
            ],
            colors: ['#5156be', '#00bfa5'],
            plotOptions: { bar: { dataLabels: { enabled: false }, columnWidth: '70%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'] },
            yaxis: {
                title: { text: 'Doanh Thu (VNĐ)' },
                labels: { formatter: val => Number(val).toLocaleString('vi-VN') + 'đ' }
            },
            tooltip: { y: { formatter: val => Number(val).toLocaleString('vi-VN') + 'đ' } }
        }).render();

        new ApexCharts(document.querySelector("#room-status-chart"), {
            chart: { type: 'donut', height: 350 },
            series: [{{ $occupiedRooms }}, {{ $availableRooms }}, {{ $maintenanceRooms }}],
            labels: ['Đã Thuê', 'Còn Trống', 'Bảo Trì'],
            colors: ['#ffc107', '#00bfa5', '#ef5350'],
            plotOptions: { pie: { donut: { size: '70%' } } },
            dataLabels: { enabled: true, formatter: val => Math.round(val) + '%' },
            legend: { position: 'bottom' }
        }).render();
    </script>
@endpush
