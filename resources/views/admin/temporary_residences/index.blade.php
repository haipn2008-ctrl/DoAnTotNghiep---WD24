@extends('layouts.admin.index')

@section('title', 'Quản lý tạm trú')

@section('content')

    <style>
        .temporary-page {
            background: #f8f9fc;
        }

        .page-header {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }

        .page-header-icon {
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

        .filter-card,
        .table-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .filter-card .card-body {
            padding: 20px;
        }

        .table-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eff0f2;
            padding: 16px 20px;
        }

        .table-card .card-body {
            padding: 0;
        }

        .form-label {
            font-weight: 500;
            color: #343a40;
            margin-bottom: 7px;
        }

        .form-control,
        .form-select {
            min-height: 42px;
            border-radius: 8px;
            border-color: #dfe3e8;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #556ee6;
            box-shadow: 0 0 0 .15rem rgba(85, 110, 230, .12);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8f9fc;
            color: #74788d;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
            padding: 14px 16px;
            border-bottom: 1px solid #e9ecef;
        }

        .table tbody td {
            padding: 15px 16px;
            vertical-align: middle;
            border-color: #f0f1f3;
        }

        .table tbody tr:hover {
            background: #fafbff;
        }

        .tenant-name {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 3px;
        }

        .tenant-phone {
            font-size: 12px;
            color: #74788d;
        }

        .contract-code {
            font-weight: 600;
            color: #556ee6;
            font-size: 13px;
        }

        .room-code {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            background: rgba(85, 110, 230, .08);
            color: #556ee6;
            font-size: 12px;
            font-weight: 600;
        }

        .date-value {
            font-size: 13px;
            color: #343a40;
            font-weight: 500;
            white-space: nowrap;
        }

        .date-end {
            color: #74788d;
            font-size: 12px;
            margin-top: 3px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-expired {
            background: #e2e3e5;
            color: #41464b;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .action-buttons .btn {
            width: 34px;
            height: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
        }

        .empty-state {
            padding: 55px 20px;
            text-align: center;
        }

        .empty-state-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f3f5;
            color: #adb5bd;
            font-size: 28px;
        }

        .empty-state h6 {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .empty-state p {
            color: #74788d;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .pagination-wrapper {
            padding: 16px 20px;
            border-top: 1px solid #eff0f2;
        }

        .result-count {
            color: #74788d;
            font-size: 13px;
        }

        .note-text {
            max-width: 180px;
            font-size: 12px;
            color: #74788d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 991px) {
            .table-responsive {
                border-radius: 0;
            }
        }
    </style>

    <div class="container-fluid temporary-page py-1">

        {{-- ============================================================
        HEADER
        ============================================================= --}}

        <div class="page-header">

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">

                <div class="d-flex align-items-center">

                    <div class="page-header-icon">
                        <i class="mdi mdi-home-map-marker"></i>
                    </div>

                    <div>
                        <h4 class="mb-1 fw-semibold">
                            Quản lý tạm trú
                        </h4>

                        <p class="text-muted mb-0 small">
                            Quản lý thông tin đăng ký tạm trú của khách thuê
                        </p>
                    </div>

                </div>

                <a href="{{ route('admin.temporary_residences.create') }}"
                    class="btn btn-primary">

                    <i class="mdi mdi-plus me-1"></i>

                    Đăng ký tạm trú

                </a>

            </div>

        </div>


        {{-- ============================================================
        SUCCESS
        ============================================================= --}}

        @if (session('success'))

            <div class="alert alert-success border-0 shadow-sm mb-4">

                <i class="mdi mdi-check-circle-outline me-1"></i>

                {{ session('success') }}

            </div>

        @endif


        {{-- ============================================================
        FILTER
        ============================================================= --}}

        <div class="filter-card">

            <div class="card-body">

                <form action="{{ route('admin.temporary_residences.index') }}"
                    method="GET">

                    <div class="row g-3 align-items-end">

                        {{-- Tìm kiếm --}}

                        <div class="col-lg-4 col-md-6">

                            <label for="search" class="form-label">
                                Tìm kiếm
                            </label>

                            <div class="input-group">

                                <span class="input-group-text bg-white">
                                    <i class="mdi mdi-magnify"></i>
                                </span>

                                <input
                                    type="text"
                                    name="search"
                                    id="search"
                                    class="form-control"
                                    value="{{ request('search') }}"
                                    placeholder="Tên, SĐT hoặc CCCD..."
                                >

                            </div>

                        </div>


                        {{-- Trạng thái --}}

                        <div class="col-lg-2 col-md-6">

                            <label for="status" class="form-label">
                                Trạng thái
                            </label>

                            <select name="status"
                                id="status"
                                class="form-select">

                                <option value="">
                                    Tất cả
                                </option>

                                <option value="pending"
                                    @selected(request('status') === 'pending')>
                                    Chờ xác nhận
                                </option>

                                <option value="active"
                                    @selected(request('status') === 'active')>
                                    Đang tạm trú
                                </option>

                                <option value="expired"
                                    @selected(request('status') === 'expired')>
                                    Đã hết hạn
                                </option>

                                <option value="cancelled"
                                    @selected(request('status') === 'cancelled')>
                                    Đã hủy
                                </option>

                            </select>

                        </div>


                        {{-- Từ ngày --}}

                        <div class="col-lg-2 col-md-6">

                            <label for="start_date" class="form-label">
                                Từ ngày
                            </label>

                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
                                class="form-control"
                                value="{{ request('start_date') }}"
                            >

                        </div>


                        {{-- Đến ngày --}}

                        <div class="col-lg-2 col-md-6">

                            <label for="end_date" class="form-label">
                                Đến ngày
                            </label>

                            <input
                                type="date"
                                name="end_date"
                                id="end_date"
                                class="form-control"
                                value="{{ request('end_date') }}"
                            >

                        </div>


                        {{-- Button --}}

                        <div class="col-lg-2 col-md-12">

                            <div class="d-flex gap-2">

                                <button type="submit"
                                    class="btn btn-primary flex-grow-1">

                                    <i class="mdi mdi-filter-outline me-1"></i>

                                    Lọc

                                </button>

                                <a href="{{ route('admin.temporary_residences.index') }}"
                                    class="btn btn-light"
                                    title="Xóa bộ lọc">

                                    <i class="mdi mdi-refresh"></i>

                                </a>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        {{-- ============================================================
        TABLE
        ============================================================= --}}

        <div class="table-card">

            <div class="card-header">

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">

                    <div>

                        <h5 class="mb-1 fw-semibold">

                            <i class="mdi mdi-format-list-bulleted text-primary me-1"></i>

                            Danh sách đăng ký tạm trú

                        </h5>

                        <div class="result-count">

                            Tổng cộng:
                            <strong>
                                {{ $temporaryResidences->total() }}
                            </strong>
                            hồ sơ

                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body">

                @if ($temporaryResidences->count())

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th style="width: 60px;">
                                        #
                                    </th>

                                    <th>
                                        Khách thuê
                                    </th>

                                    <th>
                                        Phòng
                                    </th>

                                    <th>
                                        Hợp đồng
                                    </th>

                                    <th>
                                        Thời gian tạm trú
                                    </th>

                                    <th>
                                        Trạng thái
                                    </th>

                                    <th>
                                        Ghi chú
                                    </th>

                                    <th class="text-center">
                                        Thao tác
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @foreach ($temporaryResidences as $temporaryResidence)

                                    <tr>

                                        {{-- STT --}}

                                        <td>

                                            <span class="text-muted fw-semibold">

                                                {{ $temporaryResidences->firstItem() + $loop->index }}

                                            </span>

                                        </td>


                                        {{-- KHÁCH --}}

                                        <td>

                                            <div class="tenant-name">

                                                {{ $temporaryResidence->tenant->full_name ?? 'N/A' }}

                                            </div>

                                            @if ($temporaryResidence->tenant?->phone)

                                                <div class="tenant-phone">

                                                    <i class="mdi mdi-phone-outline me-1"></i>

                                                    {{ $temporaryResidence->tenant->phone }}

                                                </div>

                                            @endif

                                        </td>


                                        {{-- PHÒNG --}}

                                        <td>

                                            @if ($temporaryResidence->contract?->room)

                                                <span class="room-code">

                                                    <i class="mdi mdi-home-outline me-1"></i>

                                                    {{ $temporaryResidence->contract->room->room_code }}

                                                </span>

                                            @else

                                                <span class="text-muted">
                                                    N/A
                                                </span>

                                            @endif

                                        </td>


                                        {{-- HỢP ĐỒNG --}}

                                        <td>

                                            @if ($temporaryResidence->contract)

                                                <div class="contract-code">

                                                    {{ $temporaryResidence->contract->contract_code
                                                        ?? 'HD-' . $temporaryResidence->contract->id }}

                                                </div>

                                            @else

                                                <span class="text-muted">
                                                    N/A
                                                </span>

                                            @endif

                                        </td>


                                        {{-- THỜI GIAN --}}

                                        <td>

                                            <div class="date-value">

                                                <i class="mdi mdi-calendar-start me-1 text-primary"></i>

                                                {{ optional($temporaryResidence->start_date)->format('d/m/Y') ?? 'N/A' }}

                                            </div>

                                            <div class="date-end">

                                                <i class="mdi mdi-calendar-end me-1"></i>

                                                @if ($temporaryResidence->end_date)

                                                    {{ $temporaryResidence->end_date->format('d/m/Y') }}

                                                @else

                                                    Không xác định

                                                @endif

                                            </div>

                                        </td>


                                        {{-- TRẠNG THÁI --}}

                                        <td>

                                            @switch($temporaryResidence->status)

                                                @case('pending')

                                                    <span class="status-badge status-pending">

                                                        <i class="mdi mdi-clock-outline"></i>

                                                        Chờ xác nhận

                                                    </span>

                                                    @break

                                                @case('active')

                                                    <span class="status-badge status-active">

                                                        <i class="mdi mdi-check-circle-outline"></i>

                                                        Đang tạm trú

                                                    </span>

                                                    @break

                                                @case('expired')

                                                    <span class="status-badge status-expired">

                                                        <i class="mdi mdi-calendar-remove-outline"></i>

                                                        Đã hết hạn

                                                    </span>

                                                    @break

                                                @case('cancelled')

                                                    <span class="status-badge status-cancelled">

                                                        <i class="mdi mdi-close-circle-outline"></i>

                                                        Đã hủy

                                                    </span>

                                                    @break

                                                @default

                                                    <span class="status-badge status-expired">
                                                        Không xác định
                                                    </span>

                                            @endswitch

                                        </td>


                                        {{-- GHI CHÚ --}}

                                        <td>

                                            @if ($temporaryResidence->note)

                                                <div class="note-text"
                                                    title="{{ $temporaryResidence->note }}">

                                                    {{ $temporaryResidence->note }}

                                                </div>

                                            @else

                                                <span class="text-muted small">
                                                    —
                                                </span>

                                            @endif

                                        </td>


                                        {{-- THAO TÁC --}}

                                        <td>

                                            <div class="action-buttons justify-content-center">

                                                {{-- XEM --}}

                                                <a href="{{ route(
                                                    'admin.temporary_residences.show',
                                                    $temporaryResidence
                                                ) }}"
                                                    class="btn btn-soft-primary"
                                                    title="Xem chi tiết">

                                                    <i class="mdi mdi-eye-outline"></i>

                                                </a>


                                                @if (!$temporaryResidence->signature && !$temporaryResidence->signed_at && $temporaryResidence->status !== 'cancelled')
                                                {{-- SỬA --}}

                                                <a href="{{ route(
                                                    'admin.temporary_residences.edit',
                                                    $temporaryResidence
                                                ) }}"
                                                    class="btn btn-soft-warning"
                                                    title="Chỉnh sửa">

                                                    <i class="mdi mdi-pencil-outline"></i>

                                                </a>


                                                {{-- HỦY HỒ SƠ --}}

                                                <form action="{{ route(
                                                    'admin.temporary_residences.destroy',
                                                    $temporaryResidence
                                                ) }}"
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="const reason = prompt('Nhập lý do hủy hồ sơ tạm trú (ít nhất 10 ký tự):'); if (!reason || reason.trim().length < 10) { alert('Lý do hủy phải có ít nhất 10 ký tự.'); return false; } this.elements.cancellation_reason.value = reason.trim(); return confirm('Xác nhận hủy hồ sơ này? Dữ liệu sẽ được giữ lại để truy vết.');">

                                                    @csrf

                                                    @method('DELETE')

                                                    <input type="hidden" name="cancellation_reason">

                                                    <button type="submit"
                                                        class="btn btn-soft-danger"
                                                        title="Hủy hồ sơ">

                                                        <i class="mdi mdi-cancel"></i>

                                                    </button>

                                                </form>
                                                @elseif ($temporaryResidence->signature || $temporaryResidence->signed_at)
                                                    <span class="badge bg-success-subtle text-success">Đã khóa sau khi ký</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Đã hủy</span>
                                                @endif

                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    {{-- PAGINATION --}}

                    @if ($temporaryResidences->hasPages())

                        <div class="pagination-wrapper">

                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">

                                <div class="result-count">

                                    Hiển thị
                                    <strong>
                                        {{ $temporaryResidences->firstItem() }}
                                    </strong>

                                    đến

                                    <strong>
                                        {{ $temporaryResidences->lastItem() }}
                                    </strong>

                                    trong tổng số

                                    <strong>
                                        {{ $temporaryResidences->total() }}
                                    </strong>

                                    hồ sơ

                                </div>

                                <div>
                                    {{ $temporaryResidences->links() }}
                                </div>

                            </div>

                        </div>

                    @endif

                @else

                    {{-- EMPTY --}}

                    <div class="empty-state">

                        <div class="empty-state-icon">

                            <i class="mdi mdi-home-map-marker"></i>

                        </div>

                        <h6>
                            Chưa có đăng ký tạm trú
                        </h6>

                        <p>
                            Chưa tìm thấy hồ sơ tạm trú phù hợp với điều kiện tìm kiếm.
                        </p>

                        <a href="{{ route('admin.temporary_residences.create') }}"
                            class="btn btn-primary">

                            <i class="mdi mdi-plus me-1"></i>

                            Đăng ký tạm trú

                        </a>

                    </div>

                @endif

            </div>

        </div>

    </div>

@endsection
