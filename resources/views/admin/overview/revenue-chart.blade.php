@extends('layouts.admin.index')

@section('title', 'Biểu đồ doanh thu | Quản lý phòng trọ')
@section('page_title', 'Biểu đồ doanh thu')

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-slate-500">Tổng quan</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-950">Biểu đồ doanh thu tháng/năm</h2>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-950">Doanh thu theo tháng - Năm {{ $currentYear }}</h3>
            <div id="monthlyRevenueChart" class="mt-4 min-h-[360px]"></div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-950">Doanh thu theo năm</h3>
            <div id="yearlyRevenueChart" class="mt-4 min-h-[360px]"></div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        new ApexCharts(document.querySelector("#monthlyRevenueChart"), {
            chart: { type: 'bar', height: 360, toolbar: { show: true } },
            series: [{ name: 'Doanh Thu (VNĐ)', data: @json($monthlyRevenue) }],
            colors: ['#5156be'],
            plotOptions: { bar: { columnWidth: '60%' } },
            dataLabels: {
                enabled: true,
                formatter: function(val) { return '₫ ' + Number(val).toLocaleString('vi-VN'); }
            },
            xaxis: {
                categories: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                             'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                title: { text: 'Tháng' }
            },
            yaxis: { title: { text: 'Doanh Thu (VNĐ)' } },
            tooltip: { y: { formatter: function(val) { return '₫ ' + Number(val).toLocaleString('vi-VN'); } } }
        }).render();

        new ApexCharts(document.querySelector("#yearlyRevenueChart"), {
            chart: { type: 'line', height: 360, toolbar: { show: true } },
            series: [{ name: 'Doanh Thu (VNĐ)', data: @json($yearlyRevenue) }],
            xaxis: { categories: @json($yearLabels), title: { text: 'Năm' } },
            yaxis: { title: { text: 'Doanh Thu (VNĐ)' } },
            stroke: { curve: 'smooth' },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: function(val) { return '₫ ' + Number(val).toLocaleString('vi-VN'); } } }
        }).render();
    </script>
@endpush
