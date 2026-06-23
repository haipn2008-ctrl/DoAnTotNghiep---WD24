@extends('layouts.admin.index')

@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">
                        Thống Kê Số Phòng
                    </h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.overview') }}">
                                    Tổng Quan
                                </a>
                            </li>
                            <li class="breadcrumb-item active">
                                Thống Kê Phòng
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- Room Statistics Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Tổng Số Phòng
                                </span>
                                <h4 class="mb-3">
                                    {{ $totalRooms }}
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-home" style="font-size: 40px; color: #5156be;"></i>
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
                                    Phòng Đã Cho Thuê
                                </span>
                                <h4 class="mb-3">
                                    {{ $occupiedRooms }}
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-account" style="font-size: 40px; color: #00bfa5;"></i>
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
                                    Phòng Còn Trống
                                </span>
                                <h4 class="mb-3">
                                    {{ $availableRooms }}
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-home-outline" style="font-size: 40px; color: #ffc107;"></i>
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
                                    Phòng Bảo Trì
                                </span>
                                <h4 class="mb-3">
                                    {{ $maintenanceRooms }}
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-wrench" style="font-size: 40px; color: #ef5350;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Statistics Chart -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Biểu Đồ Trạng Thái Phòng</h5>
                    </div>
                    <div class="card-body">
                        <div id="roomStatusChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Details Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Chi Tiết Trạng Thái Phòng</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Trạng Thái</th>
                                        <th class="text-right">Số Lượng</th>
                                        <th class="text-right">Tỷ Lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><i class="mdi mdi-account" style="color: #00bfa5;"></i> Đã Cho Thuê</td>
                                        <td class="text-right">{{ $occupiedRooms }}</td>
                                        <td class="text-right">{{ ($totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0) }}%</td>
                                    </tr>
                                    <tr>
                                        <td><i class="mdi mdi-home-outline" style="color: #ffc107;"></i> Còn Trống</td>
                                        <td class="text-right">{{ $availableRooms }}</td>
                                        <td class="text-right">{{ ($totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100, 1) : 0) }}%</td>
                                    </tr>
                                    <tr>
                                        <td><i class="mdi mdi-wrench" style="color: #ef5350;"></i> Bảo Trì</td>
                                        <td class="text-right">{{ $maintenanceRooms }}</td>
                                        <td class="text-right">{{ ($totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0) }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            var options = {
                chart: {
                    type: 'donut',
                    height: 350
                },
                series: [{{ $occupiedRooms }}, {{ $availableRooms }}, {{ $maintenanceRooms }}],
                labels: ['Đã Cho Thuê', 'Còn Trống', 'Bảo Trì'],
                colors: ['#00bfa5', '#ffc107', '#ef5350'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '75%'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return Math.round(val) + '%';
                    }
                },
                legend: {
                    position: 'bottom'
                }
            };

            var chart = new ApexCharts(document.querySelector("#roomStatusChart"), options);
            chart.render();
        </script>
    @endpush
@endsection
