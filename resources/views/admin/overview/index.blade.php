@extends('layouts.admin.index')

@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">
                        Tổng Quan
                    </h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">
                                <a href="javascript: void(0);">
                                    Admin
                                </a>
                            </li>
                            <li class="breadcrumb-item active">
                                Tổng Quan
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Tổng Doanh Thu
                                </span>
                                <h4 class="mb-3">
                                    {{ number_format($totalRevenue / 1000000, 1) }}M
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-currency-usd" style="font-size: 40px; color: #5156be;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Tổng Phòng
                                </span>
                                <h4 class="mb-3">
                                    {{ $totalRooms }}
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-home" style="font-size: 40px; color: #00bfa5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Hợp Đồng Hoạt Động
                                </span>
                                <h4 class="mb-3">
                                    {{ $activeContracts }}
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-file-document" style="font-size: 40px; color: #ffc107;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Hóa Đơn Chưa Thanh Toán
                                </span>
                                <h4 class="mb-3">
                                    {{ $unpaidInvoices }}
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-alert-circle" style="font-size: 40px; color: #ef5350;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row">
            <!-- Doanh Thu Theo Tháng (Cột) -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Doanh Thu Theo Tháng - Năm 2025 & 2026</h5>
                    </div>
                    <div class="card-body">
                        <div id="monthly-revenue-chart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>

            <!-- Tỷ Lệ Phòng (Tròn) -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tỷ Lệ Trạng Thái Phòng</h5>
                    </div>
                    <div class="card-body">
                        <div id="room-status-chart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row">
            <!-- Trạng Thái Hóa Đơn (Tròn) -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Trạng Thái Hóa Đơn</h5>
                    </div>
                    <div class="card-body">
                        <div id="invoice-status-chart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>

            <!-- Thống Kê Nhanh -->
            <div class="col-lg-7">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-2">Doanh Thu Hôm Nay</p>
                                        <h4 class="mb-0">{{ number_format($todayRevenue, 0, ',', '.') }} đ</h4>
                                    </div>
                                    <div class="text-center">
                                        <i class="mdi mdi-cash-multiple" style="font-size: 40px; color: #5156be; opacity: 0.3;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-2">Doanh Thu Tháng Này</p>
                                        <h4 class="mb-0">{{ number_format($monthRevenue, 0, ',', '.') }} đ</h4>
                                    </div>
                                    <div class="text-center">
                                        <i class="mdi mdi-calendar" style="font-size: 40px; color: #00bfa5; opacity: 0.3;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Chi Tiết Phòng</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 col-sm-4">
                                        <div class="mb-3">
                                            <h5>{{ $occupiedRooms }}</h5>
                                            <p class="text-muted mb-0">Đã Thuê</p>
                                            <small class="text-success">{{ $occupiedPercent }}%</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4">
                                        <div class="mb-3">
                                            <h5>{{ $availableRooms }}</h5>
                                            <p class="text-muted mb-0">Còn Trống</p>
                                            <small class="text-info">{{ $availablePercent }}%</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-4">
                                        <div class="mb-3">
                                            <h5>{{ $maintenanceRooms }}</h5>
                                            <p class="text-muted mb-0">Bảo Trì</p>
                                            <small class="text-warning">{{ $maintenancePercent }}%</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
        <script>
            // 1. Biểu đồ cột - Doanh Thu Theo Tháng
            var monthlyOptions = {
                series: [{
                    name: 'Doanh Thu 2025',
                    data: [
                        @for($month = 0; $month < 12; $month++)
                            {{ $monthlyRevenue2025[$month] ?? 0 }}{{ $month < 11 ? ',' : '' }}
                        @endfor
                    ]
                },
                {
                    name: 'Doanh Thu 2026',
                    data: [
                        @for($month = 0; $month < 12; $month++)
                            {{ $monthlyRevenue2026[$month] ?? 0 }}{{ $month < 11 ? ',' : '' }}
                        @endfor
                    ]
                }],
                chart: {
                    type: 'column',
                    height: 350,
                    toolbar: { show: false }
                },
                plotOptions: {
                    column: {
                        dataLabels: { enabled: false },
                        columnWidth: '70%'
                    }
                },
                colors: ['#5156be'],
                xaxis: {
                    categories: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12']
                },
                yaxis: {
                    title: { text: 'Doanh Thu (VNĐ)' }
                }
            };

            var monthlyChart = new ApexCharts(document.querySelector("#monthly-revenue-chart"), monthlyOptions);
            monthlyChart.render();

            // 2. Biểu đồ tròn - Trạng Thái Phòng
            var roomStatusOptions = {
                series: [{{ $occupiedRooms }}, {{ $availableRooms }}, {{ $maintenanceRooms }}],
                labels: ['Đã Thuê', 'Còn Trống', 'Bảo Trì'],
                chart: {
                    type: 'donut',
                    height: 350
                },
                colors: ['#ffc107', '#00bfa5', '#ef5350'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return Math.round(val) + '%'
                    }
                },
                legend: {
                    position: 'bottom'
                }
            };

            var roomStatusChart = new ApexCharts(document.querySelector("#room-status-chart"), roomStatusOptions);
            roomStatusChart.render();

            // 3. Biểu đồ tròn - Trạng Thái Hóa Đơn
            var invoiceStatusOptions = {
                series: [{{ $paidInvoices }}, {{ $unpaidInvoices }}, {{ $partialInvoices }}],
                labels: ['Đã Thanh Toán', 'Chưa Thanh Toán', 'Thanh Toán Một Phần'],
                chart: {
                    type: 'pie',
                    height: 350
                },
                colors: ['#00bfa5', '#ef5350', '#ffc107'],
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return Math.round(val) + '%'
                    }
                },
                legend: {
                    position: 'bottom'
                }
            };

            var invoiceStatusChart = new ApexCharts(document.querySelector("#invoice-status-chart"), invoiceStatusOptions);
            invoiceStatusChart.render();
        </script>
    @endpush
@endsection
