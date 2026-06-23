@extends('layouts.admin.index')

@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">
                        Thống Kê Tổng Doanh Thu
                    </h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.overview') }}">
                                    Tổng Quan
                                </a>
                            </li>
                            <li class="breadcrumb-item active">
                                Thống Kê Doanh Thu
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- Revenue Statistics -->
        <div class="row">
            <div class="col-xl-4 col-md-6">
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

            <div class="col-xl-4 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Doanh Thu Tháng Này
                                </span>
                                <h4 class="mb-3">
                                    {{ number_format($monthRevenue / 1000000, 1) }}M
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-calendar-month" style="font-size: 40px; color: #ffc107;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Doanh Thu Hôm Nay
                                </span>
                                <h4 class="mb-3">
                                    {{ number_format($todayRevenue / 1000000, 1) }}M
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <i class="mdi mdi-today" style="font-size: 40px; color: #00bfa5;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Details -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Chi Tiết Doanh Thu</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Loại Doanh Thu</th>
                                        <th class="text-right">Giá Trị</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Tổng Doanh Thu</td>
                                        <td class="text-right"><strong>{{ number_format($totalRevenue, 0, ',', '.') }} VNĐ</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Doanh Thu Tháng Này</td>
                                        <td class="text-right">{{ number_format($monthRevenue, 0, ',', '.') }} VNĐ</td>
                                    </tr>
                                    <tr>
                                        <td>Doanh Thu Hôm Nay</td>
                                        <td class="text-right">{{ number_format($todayRevenue, 0, ',', '.') }} VNĐ</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
