@extends('layouts.admin.index')

@section('title', 'Tổng quan | Quản lý phòng trọ')
@section('page_title', 'Tổng quan')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Báo cáo vận hành</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Tổng quan hệ thống</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tổng doanh thu</p><p class="mt-3 text-3xl font-bold text-emerald-700">{{ number_format($totalRevenue, 0, ',', '.') }}đ</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tổng phòng</p><p class="mt-3 text-3xl font-bold text-slate-950">{{ $totalRooms }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Hợp đồng hoạt động</p><p class="mt-3 text-3xl font-bold text-indigo-700">{{ $activeContracts }}</p></div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Hóa đơn chưa thanh toán</p><p class="mt-3 text-3xl font-bold text-amber-700">{{ $unpaidInvoices }}</p></div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Doanh thu theo tháng</h3>
                <p class="mt-1 text-sm text-slate-500">So sánh năm 2025 và 2026</p>
                <div id="monthly-revenue-chart" class="mt-4 min-h-[350px]"></div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Trạng thái phòng</h3>
                <div id="room-status-chart" class="mt-4 min-h-[350px]"></div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_1.35fr]">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-950">Trạng thái hóa đơn</h3>
                <div id="invoice-status-chart" class="mt-4 min-h-[350px]"></div>
            </section>

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
                { name: 'Doanh thu 2025', data: @json($monthlyRevenue2025) },
                { name: 'Doanh thu 2026', data: @json($monthlyRevenue2026) }
            ],
            colors: ['#4f46e5', '#059669'],
            plotOptions: { bar: { borderRadius: 5, columnWidth: '60%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'] },
            yaxis: { labels: { formatter: val => Number(val).toLocaleString('vi-VN') + 'đ' } },
            tooltip: { y: { formatter: val => Number(val).toLocaleString('vi-VN') + 'đ' } }
        }).render();

        new ApexCharts(document.querySelector("#room-status-chart"), {
            chart: { type: 'donut', height: 350 },
            series: [{{ $occupiedRooms }}, {{ $availableRooms }}, {{ $maintenanceRooms }}],
            labels: ['Đã thuê', 'Còn trống', 'Bảo trì'],
            colors: ['#059669', '#0284c7', '#d97706'],
            legend: { position: 'bottom' }
        }).render();

        new ApexCharts(document.querySelector("#invoice-status-chart"), {
            chart: { type: 'pie', height: 350 },
            series: [{{ $paidInvoices }}, {{ $unpaidInvoices }}, {{ $partialInvoices }}],
            labels: ['Đã thanh toán', 'Chưa thanh toán', 'Thanh toán một phần'],
            colors: ['#059669', '#d97706', '#0284c7'],
            legend: { position: 'bottom' }
        }).render();
    </script>
@endpush
