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
            <h3 class="font-semibold text-slate-950">Doanh thu theo tháng - Năm {{ date('Y') }}</h3>
            <div id="monthlyRevenueChart" class="mt-4 min-h-[360px]"></div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        new ApexCharts(document.querySelector("#monthlyRevenueChart"), {
            chart: { type: 'bar', height: 360, toolbar: { show: true } },
            series: [{ name: 'Doanh thu', data: @json($monthlyRevenue) }],
            colors: ['#4f46e5'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            xaxis: { categories: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'] },
            yaxis: { labels: { formatter: val => Number(val).toLocaleString('vi-VN') + 'đ' } },
            tooltip: { y: { formatter: val => Number(val).toLocaleString('vi-VN') + 'đ' } }
        }).render();
    </script>
@endpush
