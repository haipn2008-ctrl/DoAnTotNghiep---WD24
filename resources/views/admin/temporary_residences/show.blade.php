@extends('layouts.admin.index')

@section('title', 'Chi tiết đăng ký tạm trú')

@section('content')

<style>
    .temporary-page {
        background: #f8f9fc;
    }

    .temporary-header {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
    }

    .temporary-header-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(85, 110, 230, .1);
        color: #556ee6;
        font-size: 22px;
        margin-right: 12px;
    }

    .detail-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
        overflow: hidden;
        margin-bottom: 20px;
        background: #fff;
    }

    .detail-card .card-header {
        background: #fff;
        border-bottom: 1px solid #eff0f2;
        padding: 16px 20px;
    }

    .detail-card .card-body {
        padding: 20px;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        font-weight: 600;
        margin: 0;
    }

    .section-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(85, 110, 230, .1);
        color: #556ee6;
        flex-shrink: 0;
    }

    .info-item {
        height: 100%;
        padding: 13px 15px;
        border: 1px solid #edf0f3;
        border-radius: 9px;
        background: #f8f9fc;
    }

    .info-label {
        display: block;
        font-size: 12px;
        color: #74788d;
        margin-bottom: 5px;
    }

    .info-value {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #343a40;
        word-break: break-word;
    }

    .info-value.muted {
        color: #74788d;
        font-weight: 500;
    }

    .status-box {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        border-radius: 10px;
        background: #f8f9fc;
        border: 1px solid #edf0f3;
    }

    .status-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .status-icon.pending {
        background: rgba(241, 180, 76, .12);
        color: #f1b44c;
    }

    .status-icon.active {
        background: rgba(52, 195, 143, .12);
        color: #34c38f;
    }

    .status-icon.expired {
        background: rgba(116, 120, 141, .12);
        color: #74788d;
    }

    .status-icon.cancelled {
        background: rgba(244, 106, 106, .12);
        color: #f46a6a;
    }

    .date-card {
        padding: 18px;
        border: 1px solid #edf0f3;
        border-radius: 10px;
        background: #f8f9fc;
        text-align: center;
        height: 100%;
    }

    .date-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(85, 110, 230, .1);
        color: #556ee6;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .date-label {
        display: block;
        font-size: 12px;
        color: #74788d;
        margin-bottom: 4px;
    }

    .date-value {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: #343a40;
    }

    .date-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #74788d;
        font-size: 22px;
    }

    .note-box {
        padding: 15px;
        border-radius: 9px;
        background: #f8f9fc;
        border: 1px solid #edf0f3;
        color: #495057;
        line-height: 1.6;
        white-space: pre-line;
        min-height: 70px;
    }

    .summary-card {
        position: sticky;
        top: 20px;
    }

    .side-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .side-card-header {
        padding: 16px 18px;
        border-bottom: 1px solid #eff0f2;
        font-weight: 600;
    }

    .side-card-body {
        padding: 18px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px dashed #e5e7eb;
    }

    .summary-row:last-child {
        border-bottom: 0;
    }

    .summary-label {
        color: #74788d;
        font-size: 12px;
        flex-shrink: 0;
    }

    .summary-value {
        text-align: right;
        font-size: 13px;
        font-weight: 600;
        color: #343a40;
        word-break: break-word;
    }

    .vehicle-item {
        padding: 14px;
        border: 1px solid #edf0f3;
        border-radius: 9px;
        background: #f8f9fc;
        height: 100%;
    }

    .vehicle-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(85, 110, 230, .1);
        color: #556ee6;
        font-size: 18px;
    }

    .action-card {
        border: 0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
    }

    .btn-action {
        min-height: 42px;
        border-radius: 8px;
        font-weight: 500;
    }

    @media (max-width: 991px) {
        .summary-card {
            position: static;
        }

        .date-arrow {
            height: auto;
            padding: 8px 0;
        }
    }
</style>


<div class="container-fluid temporary-page py-1">

    {{-- ================================================================
    HEADER
    ================================================================= --}}

    <div class="temporary-header">

        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">

            <div class="d-flex align-items-center">

                <div class="temporary-header-icon">
                    <i class="mdi mdi-home-map-marker"></i>
                </div>

                <div>

                    <h4 class="mb-1 fw-semibold">
                        Chi tiết đăng ký tạm trú
                    </h4>

                    <p class="text-muted mb-0 small">
                        Xem thông tin chi tiết hồ sơ đăng ký tạm trú
                    </p>

                </div>

            </div>


            <div class="d-flex gap-2">

                <a href="{{ route('admin.temporary_residences.index') }}"
                    class="btn btn-light btn-action">

                    <i class="mdi mdi-arrow-left me-1"></i>

                    Quay lại

                </a>


                <a href="{{ route('admin.temporary_residences.edit', $temporaryResidence) }}"
                    class="btn btn-primary btn-action">

                    <i class="mdi mdi-pencil-outline me-1"></i>

                    Chỉnh sửa

                </a>

            </div>

        </div>

    </div>


    <div class="row">

        {{-- ============================================================
        LEFT
        ============================================================= --}}

        <div class="col-lg-8">


            {{-- ========================================================
            1. THÔNG TIN KHÁCH THUÊ
            ========================================================= --}}

            <div class="detail-card">

                <div class="card-header">

                    <h5 class="section-title">

                        <span class="section-icon">
                            <i class="mdi mdi-account-outline"></i>
                        </span>

                        Thông tin khách thuê

                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Họ và tên
                                </span>

                                <span class="info-value">
                                    {{ $temporaryResidence->tenant->full_name ?? 'Chưa cập nhật' }}
                                </span>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Số điện thoại
                                </span>

                                <span class="info-value">
                                    {{ $temporaryResidence->tenant->phone ?? 'Chưa cập nhật' }}
                                </span>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    CCCD
                                </span>

                                <span class="info-value">
                                    {{ $temporaryResidence->tenant->cccd ?? 'Chưa cập nhật' }}
                                </span>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Địa chỉ thường trú
                                </span>

                                <span class="info-value">
                                    {{ $temporaryResidence->tenant->address ?? 'Chưa cập nhật' }}
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ========================================================
            2. THÔNG TIN HỢP ĐỒNG
            ========================================================= --}}

            <div class="detail-card">

                <div class="card-header">

                    <h5 class="section-title">

                        <span class="section-icon">
                            <i class="mdi mdi-file-document-outline"></i>
                        </span>

                        Thông tin hợp đồng

                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-4">

                            <div class="info-item">

                                <span class="info-label">
                                    Mã hợp đồng
                                </span>

                                <span class="info-value">
                                    {{ $temporaryResidence->contract->contract_code ?? 'HD-' . $temporaryResidence->contract_id }}
                                </span>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-item">

                                <span class="info-label">
                                    Phòng
                                </span>

                                <span class="info-value">

                                    Phòng
                                    {{ $temporaryResidence->contract->room->room_code ?? 'N/A' }}

                                </span>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="info-item">

                                <span class="info-label">
                                    Trạng thái hợp đồng
                                </span>

                                <span class="info-value">

                                    @php
                                        $contractStatus = $temporaryResidence->contract->status ?? null;
                                    @endphp

                                    @switch($contractStatus)

                                        @case('active')
                                            <span class="badge bg-success-subtle text-success">
                                                Đang hoạt động
                                            </span>
                                        @break

                                        @case('pending')
                                            <span class="badge bg-warning-subtle text-warning">
                                                Chờ xử lý
                                            </span>
                                        @break

                                        @case('signed')
                                            <span class="badge bg-info-subtle text-info">
                                                Đã ký
                                            </span>
                                        @break

                                        @case('expired')
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                Đã hết hạn
                                            </span>
                                        @break

                                        @case('terminated')
                                            <span class="badge bg-danger-subtle text-danger">
                                                Đã chấm dứt
                                            </span>
                                        @break

                                        @default
                                            <span class="badge bg-light text-dark">
                                                {{ $contractStatus ?? 'Không xác định' }}
                                            </span>

                                    @endswitch

                                </span>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Ngày bắt đầu hợp đồng
                                </span>

                                <span class="info-value">

                                    {{ $temporaryResidence->contract?->start_date
                                        ? \Carbon\Carbon::parse($temporaryResidence->contract->start_date)->format('d/m/Y')
                                        : 'Chưa xác định' }}

                                </span>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="info-item">

                                <span class="info-label">
                                    Ngày kết thúc hợp đồng
                                </span>

                                <span class="info-value">

                                    {{ $temporaryResidence->contract?->end_date
                                        ? \Carbon\Carbon::parse($temporaryResidence->contract->end_date)->format('d/m/Y')
                                        : 'Không xác định' }}

                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ========================================================
            3. THỜI GIAN TẠM TRÚ
            ========================================================= --}}

            <div class="detail-card">

                <div class="card-header">

                    <h5 class="section-title">

                        <span class="section-icon">
                            <i class="mdi mdi-calendar-clock"></i>
                        </span>

                        Thời gian tạm trú

                    </h5>

                </div>


                <div class="card-body">

                    <div class="row align-items-center g-3">

                        <div class="col-md-5">

                            <div class="date-card">

                                <div class="date-icon">
                                    <i class="mdi mdi-calendar-start"></i>
                                </div>

                                <span class="date-label">
                                    Ngày bắt đầu
                                </span>

                                <span class="date-value">

                                    {{ $temporaryResidence->start_date
                                        ? $temporaryResidence->start_date->format('d/m/Y')
                                        : 'Chưa xác định' }}

                                </span>

                            </div>

                        </div>


                        <div class="col-md-2">

                            <div class="date-arrow">
                                <i class="mdi mdi-arrow-right"></i>
                            </div>

                        </div>


                        <div class="col-md-5">

                            <div class="date-card">

                                <div class="date-icon">
                                    <i class="mdi mdi-calendar-end"></i>
                                </div>

                                <span class="date-label">
                                    Ngày kết thúc
                                </span>

                                <span class="date-value">

                                    {{ $temporaryResidence->end_date
                                        ? $temporaryResidence->end_date->format('d/m/Y')
                                        : 'Không xác định' }}

                                </span>

                            </div>

                        </div>

                    </div>


                    <div class="alert alert-info border-0 mt-3 mb-0">

                        <i class="mdi mdi-information-outline me-1"></i>

                        Thời gian tạm trú được xác định theo thời gian thuê của hợp đồng.

                    </div>

                </div>

            </div>


            {{-- ========================================================
            4. THÔNG TIN ĐĂNG KÝ
            ========================================================= --}}

            <div class="detail-card">

                <div class="card-header">

                    <h5 class="section-title">

                        <span class="section-icon">
                            <i class="mdi mdi-clipboard-text-outline"></i>
                        </span>

                        Thông tin đăng ký

                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-5">

                            <div class="status-box">

                                @php
                                    $status = $temporaryResidence->status;
                                @endphp


                                <div class="status-icon {{ $status }}">

                                    @switch($status)

                                        @case('pending')
                                            <i class="mdi mdi-clock-outline"></i>
                                        @break

                                        @case('active')
                                            <i class="mdi mdi-check-circle-outline"></i>
                                        @break

                                        @case('expired')
                                            <i class="mdi mdi-calendar-remove-outline"></i>
                                        @break

                                        @case('cancelled')
                                            <i class="mdi mdi-close-circle-outline"></i>
                                        @break

                                        @default
                                            <i class="mdi mdi-help-circle-outline"></i>

                                    @endswitch

                                </div>


                                <div>

                                    <span class="info-label mb-1">
                                        Trạng thái đăng ký
                                    </span>

                                    <span class="fw-semibold">

                                        @switch($status)

                                            @case('pending')
                                                Chờ xác nhận
                                            @break

                                            @case('active')
                                                Đang tạm trú
                                            @break

                                            @case('expired')
                                                Đã hết hạn
                                            @break

                                            @case('cancelled')
                                                Đã hủy
                                            @break

                                            @default
                                                Không xác định

                                        @endswitch

                                    </span>

                                </div>

                            </div>

                        </div>


                        <div class="col-md-7">

                            <div class="info-item">

                                <span class="info-label">
                                    Ghi chú
                                </span>

                                <div class="info-value {{ !$temporaryResidence->note ? 'muted' : '' }}">

                                    {{ $temporaryResidence->note ?? 'Không có ghi chú' }}

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ========================================================
            5. PHƯƠNG TIỆN
            ========================================================= --}}

            @if (
                $temporaryResidence->tenant &&
                $temporaryResidence->tenant->vehicles &&
                $temporaryResidence->tenant->vehicles->count()
            )

                <div class="detail-card">

                    <div class="card-header">

                        <h5 class="section-title">

                            <span class="section-icon">
                                <i class="mdi mdi-motorbike"></i>
                            </span>

                            Phương tiện của khách thuê

                        </h5>

                    </div>


                    <div class="card-body">

                        <div class="row g-3">

                            @foreach ($temporaryResidence->tenant->vehicles as $vehicle)

                                <div class="col-md-6">

                                    <div class="vehicle-item">

                                        <div class="d-flex align-items-start gap-3">

                                            <div class="vehicle-icon">

                                                <i class="mdi mdi-motorbike"></i>

                                            </div>


                                            <div class="flex-grow-1">

                                                <div class="fw-semibold mb-1">

                                                    {{ $vehicle->vehicle_name ?? $vehicle->vehicle_type ?? 'Phương tiện' }}

                                                </div>


                                                <div class="small text-muted">

                                                    Biển số:

                                                    <strong class="text-dark">

                                                        {{ $vehicle->license_plate ?? 'Chưa cập nhật' }}

                                                    </strong>

                                                </div>


                                                @if ($vehicle->color)

                                                    <div class="small text-muted mt-1">

                                                        Màu:

                                                        {{ $vehicle->color }}

                                                    </div>

                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>


            @endif



        </div>


        {{-- ============================================================
        RIGHT
        ============================================================= --}}

        <div class="col-lg-4">


            {{-- ========================================================
            SUMMARY
            ========================================================= --}}

            <div class="side-card summary-card">

                <div class="side-card-header">

                    <i class="mdi mdi-clipboard-check-outline text-primary me-1"></i>

                    Tóm tắt hồ sơ

                </div>


                <div class="side-card-body">

                    <div class="summary-row">

                        <span class="summary-label">
                            Khách thuê
                        </span>

                        <span class="summary-value">

                            {{ $temporaryResidence->tenant->full_name ?? 'Chưa cập nhật' }}

                        </span>

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Phòng
                        </span>

                        <span class="summary-value">

                            Phòng
                            {{ $temporaryResidence->contract->room->room_code ?? 'N/A' }}

                        </span>

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Hợp đồng
                        </span>

                        <span class="summary-value">

                            {{ $temporaryResidence->contract->contract_code ?? 'HD-' . $temporaryResidence->contract_id }}

                        </span>

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Thời gian
                        </span>

                        <span class="summary-value">

                            {{ $temporaryResidence->start_date
                                ? $temporaryResidence->start_date->format('d/m/Y')
                                : 'Chưa xác định' }}

                            →

                            {{ $temporaryResidence->end_date
                                ? $temporaryResidence->end_date->format('d/m/Y')
                                : 'Không xác định' }}

                        </span>

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Trạng thái
                        </span>

                        <span class="summary-value">

                            @switch($temporaryResidence->status)

                                @case('pending')
                                    <span class="badge bg-warning-subtle text-warning">
                                        Chờ xác nhận
                                    </span>
                                @break

                                @case('active')
                                    <span class="badge bg-success-subtle text-success">
                                        Đang tạm trú
                                    </span>
                                @break

                                @case('expired')
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        Đã hết hạn
                                    </span>
                                @break

                                @case('cancelled')
                                    <span class="badge bg-danger-subtle text-danger">
                                        Đã hủy
                                    </span>
                                @break

                                @default
                                    <span class="badge bg-light text-dark">
                                        Không xác định
                                    </span>

                            @endswitch

                        </span>

                    </div>

                </div>

            </div>


         {{-- ========================================================
ACTION
========================================================= --}}

<div class="side-card action-card">

    <div class="side-card-body">

        <a href="{{ route('admin.temporary_residences.pdf', $temporaryResidence) }}"
            target="_blank"
            class="btn btn-danger btn-action w-100 mb-2">

            <i class="mdi mdi-file-pdf-box me-1"></i>

            Xem phiếu PDF

        </a>

        <a href="{{ route('admin.temporary_residences.edit', $temporaryResidence) }}"
            class="btn btn-primary btn-action w-100 mb-2">

            <i class="mdi mdi-pencil-outline me-1"></i>

            Chỉnh sửa đăng ký

        </a>

        <a href="{{ route('admin.temporary_residences.index') }}"
            class="btn btn-light btn-action w-100">

            <i class="mdi mdi-arrow-left me-1"></i>

            Quay lại danh sách

        </a>

    </div>

</div>
{{-- ========================================================
KÝ XÁC NHẬN
========================================================= --}}

<div class="side-card">

    <div class="side-card-header">

        <i class="mdi mdi-draw text-primary me-1"></i>

        Ký xác nhận

    </div>

    <div class="side-card-body">

        @if ($temporaryResidence->signature)

            <div class="alert alert-success border-0 mb-3">

                <i class="mdi mdi-check-circle-outline me-1"></i>

                Hồ sơ đã được ký xác nhận.

            </div>

            <div class="text-center mb-3">

                <img
                    src="{{ $temporaryResidence->signature }}"
                    alt="Chữ ký"
                    style="
                        max-width: 100%;
                        max-height: 120px;
                        object-fit: contain;
                    "
                >

            </div>

            @if ($temporaryResidence->signed_at)

                <div class="small text-muted text-center">

                    Ký lúc:
                    {{ $temporaryResidence->signed_at->format('d/m/Y H:i') }}

                </div>

            @endif

        @else

            <p class="text-muted small mb-3">
                Vui lòng ký xác nhận hồ sơ đăng ký tạm trú trước khi xuất phiếu PDF.
            </p>

            <button
                type="button"
                class="btn btn-primary btn-action w-100"
                data-bs-toggle="modal"
                data-bs-target="#signatureModal">

                <i class="mdi mdi-draw me-1"></i>

                Ký xác nhận

            </button>

        @endif

    </div>

</div>

            {{-- ========================================================
            INFO
            ========================================================= --}}

            <div class="side-card">

                <div class="side-card-header">

                    <i class="mdi mdi-information-outline text-primary me-1"></i>

                    Thông tin hồ sơ

                </div>


                <div class="side-card-body">

                    <div class="summary-row">

                        <span class="summary-label">
                            ID hồ sơ
                        </span>

                        <span class="summary-value">
                            #{{ $temporaryResidence->id }}
                        </span>

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Tạo lúc
                        </span>

                        <span class="summary-value">

                            {{ $temporaryResidence->created_at
                                ? $temporaryResidence->created_at->format('d/m/Y H:i')
                                : 'Không xác định' }}

                        </span>

                    </div>


                    <div class="summary-row">

                        <span class="summary-label">
                            Cập nhật
                        </span>

                        <span class="summary-value">

                            {{ $temporaryResidence->updated_at
                                ? $temporaryResidence->updated_at->format('d/m/Y H:i')
                                : 'Không xác định' }}

                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
{{-- ========================================================
MODAL KÝ XÁC NHẬN
========================================================= --}}

<div class="modal fade"
    id="signatureModal"
    tabindex="-1"
    aria-labelledby="signatureModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="signatureModalLabel">

                    <i class="mdi mdi-draw text-primary me-1"></i>

                    Ký xác nhận đăng ký tạm trú

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <form
                method="POST"
                action="{{ route('admin.temporary_residences.sign', $temporaryResidence) }}">

                @csrf

                <div class="modal-body">

                    <p class="text-muted small">
                        Vui lòng ký vào khung bên dưới để xác nhận hồ sơ.
                    </p>

                    <div
                        style="
                            border: 1px solid #dee2e6;
                            border-radius: 8px;
                            overflow: hidden;
                            background: #fff;
                        "
                    >

                        <canvas
                            id="signatureCanvas"
                            width="500"
                            height="220"
                            style="
                                width: 100%;
                                height: 220px;
                                display: block;
                                cursor: crosshair;
                                touch-action: none;
                            "
                        ></canvas>

                    </div>

                    <input
                        type="hidden"
                        name="signature"
                        id="signatureInput"
                    >

                    <button
                        type="button"
                        class="btn btn-light btn-sm mt-2"
                        id="clearSignature">

                        <i class="mdi mdi-eraser me-1"></i>

                        Xóa chữ ký

                    </button>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Hủy

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="mdi mdi-check me-1"></i>

                        Lưu chữ ký

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
@endsection
<script>
document.addEventListener('DOMContentLoaded', function () {

    const canvas = document.getElementById('signatureCanvas');
    const input = document.getElementById('signatureInput');
    const clearButton = document.getElementById('clearSignature');

    if (!canvas || !input) {
        return;
    }

    const ctx = canvas.getContext('2d');

    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#000';

    let drawing = false;

    function getPosition(event) {

        const rect = canvas.getBoundingClientRect();

        let clientX;
        let clientY;

        if (event.touches && event.touches.length > 0) {

            clientX = event.touches[0].clientX;
            clientY = event.touches[0].clientY;

        } else {

            clientX = event.clientX;
            clientY = event.clientY;

        }

        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height)
        };
    }

    function startDrawing(event) {

        event.preventDefault();

        drawing = true;

        const position = getPosition(event);

        ctx.beginPath();

        ctx.moveTo(
            position.x,
            position.y
        );
    }

    function draw(event) {

        if (!drawing) {
            return;
        }

        event.preventDefault();

        const position = getPosition(event);

        ctx.lineTo(
            position.x,
            position.y
        );

        ctx.stroke();
    }

    function stopDrawing() {

        if (!drawing) {
            return;
        }

        drawing = false;

        ctx.closePath();
    }

    canvas.addEventListener(
        'mousedown',
        startDrawing
    );

    canvas.addEventListener(
        'mousemove',
        draw
    );

    canvas.addEventListener(
        'mouseup',
        stopDrawing
    );

    canvas.addEventListener(
        'mouseleave',
        stopDrawing
    );

    canvas.addEventListener(
        'touchstart',
        startDrawing,
        { passive: false }
    );

    canvas.addEventListener(
        'touchmove',
        draw,
        { passive: false }
    );

    canvas.addEventListener(
        'touchend',
        stopDrawing
    );

    clearButton?.addEventListener(
        'click',
        function () {

            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );

            input.value = '';

        }
    );

    const form = canvas.closest('form');

    form?.addEventListener(
        'submit',
        function (event) {

            const blankCanvas = document.createElement('canvas');

            blankCanvas.width = canvas.width;
            blankCanvas.height = canvas.height;

            if (
                canvas.toDataURL() ===
                blankCanvas.toDataURL()
            ) {

                event.preventDefault();

                alert('Vui lòng ký trước khi lưu.');

                return;
            }

            input.value = canvas.toDataURL('image/png');

        }
    );

});
</script>
