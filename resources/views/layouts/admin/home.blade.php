@extends('layouts.admin.index')
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@section('content')
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">
                        Trang chủ
                    </h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item">
                                <a href="javascript: void(0);">
                                    Trang chủ
                                </a>
                            </li>
                            <li class="breadcrumb-item active">
                                Trang chủ
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <!-- card -->
                <div class="card card-h-100">
                    <!-- card body -->
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    My Wallet
                                </span>
                                <h4 class="mb-3">
                                    $
                                    <span class="counter-value" data-target="865.2">
                                        0
                                    </span>
                                    k
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="apex-charts mb-2" data-colors='["#5156be"]' id="mini-chart1">
                                </div>
                            </div>
                        </div>
                        <div class="text-nowrap">
                            <span class="badge bg-success-subtle text-success">
                                +$20.9k
                            </span>
                            <span class="ms-1 text-muted font-size-13">
                                Since last week
                            </span>
                        </div>
                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->
            <div class="col-xl-3 col-md-6">
                <!-- card -->
                <div class="card card-h-100">
                    <!-- card body -->
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Số lượng giao dịch
                                </span>
                                <h4 class="mb-3">
                                    <span class="counter-value" data-target="6258">
                                        0
                                    </span>
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="apex-charts mb-2" data-colors='["#5156be"]' id="mini-chart2">
                                </div>
                            </div>
                        </div>
                        <div class="text-nowrap">
                            <span class="badge bg-danger-subtle text-danger">
                                -29 Trades
                            </span>
                            <span class="ms-1 text-muted font-size-13">
                                Since last week
                            </span>
                        </div>
                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col-->
            <div class="col-xl-3 col-md-6">
                <!-- card -->
                <div class="card card-h-100">
                    <!-- card body -->
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Số tiền đầu tư
                                </span>
                                <h4 class="mb-3">
                                    $
                                    <span class="counter-value" data-target="4.32">
                                        0
                                    </span>
                                    M
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="apex-charts mb-2" data-colors='["#5156be"]' id="mini-chart3">
                                </div>
                            </div>
                        </div>
                        <div class="text-nowrap">
                            <span class="badge bg-success-subtle text-success">
                                + $2.8k
                            </span>
                            <span class="ms-1 text-muted font-size-13">
                                Since last week
                            </span>
                        </div>
                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->
            <div class="col-xl-3 col-md-6">
                <!-- card -->
                <div class="card card-h-100">
                    <!-- card body -->
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <span class="text-muted mb-3 lh-1 d-block text-truncate">
                                    Tỷ lệ lợi nhuận
                                </span>
                                <h4 class="mb-3">
                                    <span class="counter-value" data-target="12.57">
                                        0
                                    </span>
                                    %
                                </h4>
                            </div>
                            <div class="col-6">
                                <div class="apex-charts mb-2" data-colors='["#5156be"]' id="mini-chart4">
                                </div>
                            </div>
                        </div>
                        <div class="text-nowrap">
                            <span class="badge bg-success-subtle text-success">
                                +2.95%
                            </span>
                            <span class="ms-1 text-muted font-size-13">
                                Since last week
                            </span>
                        </div>
                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->
        </div>
        <!-- end row-->
        <div class="row">
            <div class="col-xl-5">
                <!-- card -->
                <div class="card card-h-100">
                    <!-- card body -->
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center mb-4">
                            <h5 class="card-title me-2">
                                Số dư trên Ví
                            </h5>
                            <div class="ms-auto">
                                <div>
                                    <button class="btn btn-soft-secondary btn-sm" type="button">
                                        Tất cả
                                    </button>
                                    <button class="btn btn-soft-primary btn-sm" type="button">
                                        1 Tháng
                                    </button>
                                    <button class="btn btn-soft-secondary btn-sm" type="button">
                                        6 Tháng
                                    </button>
                                    <button class="btn btn-soft-secondary btn-sm" type="button">
                                        1 Năm
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-sm">
                                <div class="apex-charts" data-colors='["#777aca", "#5156be", "#a8aada"]'
                                    id="wallet-balance">
                                </div>
                            </div>
                            <div class="col-sm align-self-center">
                                <div class="mt-4 mt-sm-0">
                                    <div>
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-10 me-2 text-success">
                                            </i>
                                            Bitcoin
                                        </p>
                                        <h6>
                                            0.4412 BTC =
                                            <span class="text-muted font-size-14 fw-normal">
                                                $ 4025.32
                                            </span>
                                        </h6>
                                    </div>
                                    <div class="mt-4 pt-2">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-10 me-2 text-primary">
                                            </i>
                                            Ethereum
                                        </p>
                                        <h6>
                                            4.5701 ETH =
                                            <span class="text-muted font-size-14 fw-normal">
                                                $ 1123.64
                                            </span>
                                        </h6>
                                    </div>
                                    <div class="mt-4 pt-2">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-10 me-2 text-info">
                                            </i>
                                            Litecoin
                                        </p>
                                        <h6>
                                            35.3811 LTC =
                                            <span class="text-muted font-size-14 fw-normal">
                                                $ 2263.09
                                            </span>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->
            <div class="col-xl-7">
                <div class="row">
                    <div class="col-xl-8">
                        <!-- card -->
                        <div class="card card-h-100">
                            <!-- card body -->
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center mb-4">
                                    <h5 class="card-title me-2">
                                        Tổng quan đã đầu tư
                                    </h5>
                                    <div class="ms-auto">
                                        <select class="form-select form-select-sm">
                                            <option selected="" value="MAY">
                                                Tháng 5
                                            </option>
                                            <option value="AP">
                                                Tháng 4
                                            </option>
                                            <option value="MA">
                                                Tháng 3
                                            </option>
                                            <option value="FE">
                                                Tháng 2
                                            </option>
                                            <option value="JA">
                                                Tháng 1
                                            </option>
                                            <option value="DE">
                                                Tháng 12
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-sm">
                                        <div class="apex-charts" data-colors='["#5156be", "#34c38f"]'
                                            id="invested-overview">
                                        </div>
                                    </div>
                                    <div class="col-sm align-self-center">
                                        <div class="mt-4 mt-sm-0">
                                            <p class="mb-1">
                                                Invested Amount
                                            </p>
                                            <h4>
                                                $ 6134.39
                                            </h4>
                                            <p class="text-muted mb-4">
                                                + 0.0012.23 ( 0.2 % )
                                                <i class="mdi mdi-arrow-up ms-1 text-success">
                                                </i>
                                            </p>
                                            <div class="row g-0">
                                                <div class="col-6">
                                                    <div>
                                                        <p class="mb-2 text-muted text-uppercase font-size-11">
                                                            Income
                                                        </p>
                                                        <h5 class="fw-medium">
                                                            $ 2632.46
                                                        </h5>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div>
                                                        <p class="mb-2 text-muted text-uppercase font-size-11">
                                                            Expenses
                                                        </p>
                                                        <h5 class="fw-medium">
                                                            -$ 924.38
                                                        </h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <a class="btn btn-primary btn-sm" href="#">
                                                    View more
                                                    <i class="mdi mdi-arrow-right ms-1">
                                                    </i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end col -->
                    <div class="col-xl-4">
                        <!-- card -->
                        <div class="card bg-primary text-white shadow-primary card-h-100">
                            <!-- card body -->
                            <div class="card-body p-0">
                                <div class="carousel slide text-center widget-carousel" data-bs-ride="carousel"
                                    id="carouselExampleCaptions">
                                    <div class="carousel-inner">
                                        <div class="carousel-item active">
                                            <div class="text-center p-4">
                                                <i class="mdi mdi-bitcoin widget-box-1-icon">
                                                </i>
                                                <div class="avatar-md m-auto">
                                                    <span
                                                        class="avatar-title rounded-circle bg-light-subtle text-white font-size-24">
                                                        <i class="mdi mdi-currency-btc">
                                                        </i>
                                                    </span>
                                                </div>
                                                <h4 class="mt-3 lh-base fw-normal text-white">
                                                    <b>
                                                        Bitcoin
                                                    </b>
                                                    News
                                                </h4>
                                                <p class="text-white-50 font-size-13">
                                                    Bitcoin prices fell sharply amid the global sell-off in equities.
                                                    Negative news
                                                    over the Bitcoin past week has dampened Bitcoin basics
                                                    sentiment for bitcoin.
                                                </p>
                                                <button class="btn btn-light btn-sm" type="button">
                                                    View details
                                                    <i class="mdi mdi-arrow-right ms-1">
                                                    </i>
                                                </button>
                                            </div>
                                        </div>
                                        <!-- end carousel-item -->
                                        <div class="carousel-item">
                                            <div class="text-center p-4">
                                                <i class="mdi mdi-ethereum widget-box-1-icon">
                                                </i>
                                                <div class="avatar-md m-auto">
                                                    <span
                                                        class="avatar-title rounded-circle bg-light-subtle text-white font-size-24">
                                                        <i class="mdi mdi-ethereum">
                                                        </i>
                                                    </span>
                                                </div>
                                                <h4 class="mt-3 lh-base fw-normal text-white">
                                                    <b>
                                                        ETH
                                                    </b>
                                                    News
                                                </h4>
                                                <p class="text-white-50 font-size-13">
                                                    Bitcoin prices fell sharply amid the global sell-off in equities.
                                                    Negative news
                                                    over the Bitcoin past week has dampened Bitcoin basics
                                                    sentiment for bitcoin.
                                                </p>
                                                <button class="btn btn-light btn-sm" type="button">
                                                    View details
                                                    <i class="mdi mdi-arrow-right ms-1">
                                                    </i>
                                                </button>
                                            </div>
                                        </div>
                                        <!-- end carousel-item -->
                                        <div class="carousel-item">
                                            <div class="text-center p-4">
                                                <i class="mdi mdi-litecoin widget-box-1-icon">
                                                </i>
                                                <div class="avatar-md m-auto">
                                                    <span
                                                        class="avatar-title rounded-circle bg-light-subtle text-white font-size-24">
                                                        <i class="mdi mdi-litecoin">
                                                        </i>
                                                    </span>
                                                </div>
                                                <h4 class="mt-3 lh-base fw-normal text-white">
                                                    <b>
                                                        Litecoin
                                                    </b>
                                                    News
                                                </h4>
                                                <p class="text-white-50 font-size-13">
                                                    Bitcoin prices fell sharply amid the global sell-off in equities.
                                                    Negative news
                                                    over the Bitcoin past week has dampened Bitcoin basics
                                                    sentiment for bitcoin.
                                                </p>
                                                <button class="btn btn-light btn-sm" type="button">
                                                    View details
                                                    <i class="mdi mdi-arrow-right ms-1">
                                                    </i>
                                                </button>
                                            </div>
                                        </div>
                                        <!-- end carousel-item -->
                                    </div>
                                    <!-- end carousel-inner -->
                                    <div class="carousel-indicators carousel-indicators-rounded">
                                        <button aria-current="true" aria-label="Slide 1" class="active" data-bs-slide-to="0"
                                            data-bs-target="#carouselExampleCaptions" type="button">
                                        </button>
                                        <button aria-label="Slide 2" data-bs-slide-to="1"
                                            data-bs-target="#carouselExampleCaptions" type="button">
                                        </button>
                                        <button aria-label="Slide 3" data-bs-slide-to="2"
                                            data-bs-target="#carouselExampleCaptions" type="button">
                                        </button>
                                    </div>
                                    <!-- end carousel-indicators -->
                                </div>
                                <!-- end carousel -->
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end col -->
        </div>
    </div>
    <!-- container-fluid -->
    </div>
@endsection
