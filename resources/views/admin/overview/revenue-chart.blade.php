@extends('layouts.admin.index')

@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">
                        Biểu đồ Doanh Thu Tháng/Năm
                    </h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.overview') }}">
                                    Tổng Quan
                                </a>
                            </li>
                            <li class="breadcrumb-item active">
                                Biểu đồ Doanh Thu
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- Monthly Revenue Chart -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Doanh Thu Theo Tháng (Năm {{ $currentYear }})</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $currentMonthIndex = now()->month - 1;
                            $currentMonthRevenue = $monthlyRevenue[$currentMonthIndex] ?? 0;
                        @endphp
                        <div class="mb-3">
                            <h6>Doanh thu tháng {{ now()->month }}:</h6>
                            <h3>{{ number_format($currentMonthRevenue, 0, ',', '.') }} đ</h3>
                        </div>
                        <div id="monthlyRevenueChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Doanh Thu Theo Năm</h5>
                    </div>
                    <div class="card-body">
                        <div id="yearlyRevenueChart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            var options = {
                chart: {
                    type: 'column',
                    height: 350,
                    toolbar: {
                        show: true
                    }
                },
                colors: ['#5156be'],
                plotOptions: {
                    column: {
                        columnWidth: '60%'
                    }
                },
                series: [{
                    name: 'Doanh Thu (VNĐ)',
                    data: @json($monthlyRevenue)
                }],
                xaxis: {
                    categories: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                                'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                    title: {
                        text: 'Tháng'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Doanh Thu (VNĐ)'
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return '₫ ' + Number(val).toLocaleString('vi-VN');
                    }
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function (val) {
                            return "₫ " + Number(val).toLocaleString('vi-VN');
                        }
                    }
                }
            };

            var monthlyChart = new ApexCharts(document.querySelector("#monthlyRevenueChart"), options);
            monthlyChart.render();

            var yearlyOptions = {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: { show: true }
                },
                series: [{
                    name: 'Doanh Thu (VNĐ)',
                    data: @json($yearlyRevenue)
                }],
                xaxis: {
                    categories: @json($yearLabels),
                    title: { text: 'Năm' }
                },
                yaxis: {
                    title: { text: 'Doanh Thu (VNĐ)' }
                },
                stroke: { curve: 'smooth' },
                dataLabels: { enabled: false },
                tooltip: {
                    theme: 'light',
                    y: { formatter: function (val) { return '₫ ' + Number(val).toLocaleString('vi-VN'); } }
                }
            };

            var yearlyChart = new ApexCharts(document.querySelector("#yearlyRevenueChart"), yearlyOptions);
            yearlyChart.render();
        </script>
    @endpush
@endsection
