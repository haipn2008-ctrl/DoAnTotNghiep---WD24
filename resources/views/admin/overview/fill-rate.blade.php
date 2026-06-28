@extends('layouts.admin.index')

@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">
                        Tỷ Lệ Lấp Đầy Phòng
                    </h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.overview') }}">
                                    Tổng Quan
                                </a>
                            </li>
                            <li class="breadcrumb-item active">
                                Tỷ Lệ Lấp Đầy
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- Fill Rate Overview -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tỷ Lệ Lấp Đầy</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Tỷ Lệ Sử Dụng Phòng</h6>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width: {{ $occupiedPercent }}%;"
                                         aria-valuenow="{{ $occupiedPercent }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ $occupiedPercent }}%
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">{{ $occupiedRooms }}/{{ $totalRooms }} phòng đã cho thuê</small>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Tỷ Lệ Phòng Còn Trống</h6>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-warning" role="progressbar"
                                         style="width: {{ $availablePercent }}%;"
                                         aria-valuenow="{{ $availablePercent }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ $availablePercent }}%
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">{{ $availableRooms }}/{{ $totalRooms }} phòng còn trống</small>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Tỷ Lệ Phòng Bảo Trì</h6>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-danger" role="progressbar"
                                         style="width: {{ $maintenancePercent }}%;"
                                         aria-valuenow="{{ $maintenancePercent }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ $maintenancePercent }}%
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">{{ $maintenanceRooms }}/{{ $totalRooms }} phòng bảo trì</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fill Rate Chart -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Biểu Đồ Tỷ Lệ Lấp Đầy</h5>
                    </div>
                    <div class="card-body">
                        <div id="fillRateChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fill Rate Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Chi Tiết Tỷ Lệ</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Trạng Thái</th>
                                        <th class="text-right">Số Lượng</th>
                                        <th class="text-right">Tỷ Lệ %</th>
                                        <th>Biểu Đồ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge badge-success">Đã Cho Thuê</span></td>
                                        <td class="text-right">{{ $occupiedRooms }}</td>
                                        <td class="text-right"><strong>{{ $occupiedPercent }}%</strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" style="width: {{ $occupiedPercent }}%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-warning">Còn Trống</span></td>
                                        <td class="text-right">{{ $availableRooms }}</td>
                                        <td class="text-right"><strong>{{ $availablePercent }}%</strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-warning" style="width: {{ $availablePercent }}%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-danger">Bảo Trì</span></td>
                                        <td class="text-right">{{ $maintenanceRooms }}</td>
                                        <td class="text-right"><strong>{{ $maintenancePercent }}%</strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-danger" style="width: {{ $maintenancePercent }}%;"></div>
                                            </div>
                                        </td>
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
                    type: 'pie',
                    height: 350
                },
                series: [{{ $occupiedPercent }}, {{ $availablePercent }}, {{ $maintenancePercent }}],
                labels: ['Đã Cho Thuê (' + {{ $occupiedPercent }} + '%)', 'Còn Trống (' + {{ $availablePercent }} + '%)', 'Bảo Trì (' + {{ $maintenancePercent }} + '%)'],
                colors: ['#00bfa5', '#ffc107', '#ef5350'],
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(1) + '%';
                    }
                },
                legend: {
                    position: 'bottom'
                }
            };

            var chart = new ApexCharts(document.querySelector("#fillRateChart"), options);
            chart.render();
        </script>
    @endpush
@endsection
