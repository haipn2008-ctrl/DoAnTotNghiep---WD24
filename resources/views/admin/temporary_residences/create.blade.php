@extends('layouts.admin.index')

@section('title', 'Đăng ký tạm trú')

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

        .form-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
            overflow: hidden;
            margin-bottom: 20px;
            background: #fff;
        }

        .form-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eff0f2;
            padding: 16px 20px;
        }

        .form-card .card-body {
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

        /*
            |--------------------------------------------------------------------------
            | Preview
            |--------------------------------------------------------------------------
            */

        .tenant-preview,
        .contract-preview,
        .period-preview {
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fc;
            border: 1px solid #e9ecef;
            margin-top: 15px;
        }

        .preview-item {
            padding: 10px 12px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #edf0f3;
            height: 100%;
        }

        .preview-label {
            display: block;
            font-size: 12px;
            color: #74788d;
            margin-bottom: 3px;
        }

        .preview-value {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #343a40;
            word-break: break-word;
        }

        /*
            |--------------------------------------------------------------------------
            | Readonly date
            |--------------------------------------------------------------------------
            */

        .contract-date-input {
            background-color: #f8f9fc !important;
            cursor: not-allowed;
            color: #495057;
            font-weight: 500;
        }

        .contract-date-input:focus {
            border-color: #dfe3e8;
            box-shadow: none;
        }

        /*
            |--------------------------------------------------------------------------
            | Side cards
            |--------------------------------------------------------------------------
            */

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

        .summary-card {
            position: sticky;
            top: 20px;
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

        /*
            |--------------------------------------------------------------------------
            | Steps
            |--------------------------------------------------------------------------
            */

        .step-item {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
        }

        .step-item:last-child {
            margin-bottom: 0;
        }

        .step-number {
            flex: 0 0 32px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(85, 110, 230, .1);
            color: #556ee6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
        }

        .step-content h6 {
            font-size: 13px;
            margin: 0 0 3px;
            font-weight: 600;
        }

        .step-content p {
            margin: 0;
            font-size: 12px;
            color: #74788d;
            line-height: 1.5;
        }

        /*
            |--------------------------------------------------------------------------
            | Action
            |--------------------------------------------------------------------------
            */

        .action-card {
            border: 0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .btn-save {
            min-height: 44px;
            border-radius: 8px;
            font-weight: 500;
        }

        .required {
            color: #f46a6a;
        }

        /*
            |--------------------------------------------------------------------------
            | Empty contract
            |--------------------------------------------------------------------------
            */

        .empty-contract {
            display: none;
            margin-top: 15px;
            padding: 12px 15px;
            border-radius: 8px;
            background: #fff8e1;
            border: 1px solid #ffe082;
            color: #856404;
            font-size: 13px;
        }

        /*
            |--------------------------------------------------------------------------
            | Responsive
            |--------------------------------------------------------------------------
            */

        @media (max-width: 991px) {
            .summary-card {
                position: static;
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
                            Đăng ký tạm trú
                        </h4>

                        <p class="text-muted mb-0 small">
                            Tạo hồ sơ đăng ký tạm trú cho khách thuê
                        </p>
                    </div>

                </div>

                <a href="{{ route('admin.temporary_residences.index') }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i>
                    Quay lại
                </a>

            </div>

        </div>


        {{-- ================================================================
        ERRORS
        ================================================================= --}}

        @if ($errors->any())

            <div class="alert alert-danger border-0 shadow-sm mb-4">

                <div class="fw-semibold mb-2">
                    <i class="mdi mdi-alert-circle-outline me-1"></i>
                    Vui lòng kiểm tra lại thông tin
                </div>

                <ul class="mb-0 ps-3">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- ================================================================
        FORM
        ================================================================= --}}

        <form action="{{ route('admin.temporary_residences.store') }}" method="POST">

            @csrf

            <div class="row">

                {{-- ========================================================
                LEFT
                ========================================================= --}}

                <div class="col-lg-8">

                    {{-- ====================================================
                    1. KHÁCH THUÊ
                    ===================================================== --}}

                    <div class="form-card">

                        <div class="card-header">

                            <h5 class="section-title">

                                <span class="section-icon">
                                    <i class="mdi mdi-account-outline"></i>
                                </span>

                                Thông tin khách thuê

                            </h5>

                        </div>

                        <div class="card-body">

                            <label for="tenant_id" class="form-label">
                                Khách thuê
                                <span class="required">*</span>
                            </label>

                            <select name="tenant_id" id="tenant_id"
                                class="form-select @error('tenant_id') is-invalid @enderror" required>

                                <option value="">
                                    -- Chọn khách thuê --
                                </option>

                                @foreach ($tenants as $tenant)

                                    <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                        {{ $tenant->full_name }}

                                        @if ($tenant->phone)
                                            — {{ $tenant->phone }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                            @error('tenant_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            {{-- THÔNG TIN KHÁCH --}}

                            <div id="tenant-info" class="tenant-preview d-none">

                                <div class="row g-3">

                                    <div class="col-md-6">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                Họ và tên
                                            </span>

                                            <span id="tenant-name" class="preview-value">
                                                -
                                            </span>

                                        </div>

                                    </div>


                                    <div class="col-md-6">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                Số điện thoại
                                            </span>

                                            <span id="tenant-phone" class="preview-value">
                                                -
                                            </span>

                                        </div>

                                    </div>


                                    <div class="col-md-6">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                CCCD
                                            </span>

                                            <span id="tenant-cccd" class="preview-value">
                                                -
                                            </span>

                                        </div>

                                    </div>


                                    <div class="col-md-6">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                Địa chỉ thường trú
                                            </span>

                                            <span id="tenant-address" class="preview-value">
                                                -
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ====================================================
                    2. HỢP ĐỒNG
                    ===================================================== --}}

                    <div class="form-card">

                        <div class="card-header">

                            <h5 class="section-title">

                                <span class="section-icon">
                                    <i class="mdi mdi-file-document-outline"></i>
                                </span>

                                Hợp đồng thuê phòng

                            </h5>

                        </div>

                        <div class="card-body">

                            <label for="contract_id" class="form-label">
                                Hợp đồng
                                <span class="required">*</span>
                            </label>

                            <select name="contract_id" id="contract_id"
                                class="form-select @error('contract_id') is-invalid @enderror" required>

                                <option value="">
                                    -- Chọn hợp đồng --
                                </option>

                                @foreach ($contracts as $contract)

                                    <option value="{{ $contract->id }}" data-tenant-id="{{ $contract->tenant_id }}"
                                        data-room="{{ $contract->room->room_code ?? 'N/A' }}"
                                        data-contract="{{ $contract->contract_code ?? 'HD-' . $contract->id }}"
                                        data-start-date="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '' }}"
                                        data-end-date="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '' }}"
                                        @selected(old('contract_id') == $contract->id)>

                                        {{ $contract->contract_code ?? 'HD-' . $contract->id }}

                                        —

                                        {{ $contract->tenant->full_name ?? 'N/A' }}

                                        —

                                        Phòng {{ $contract->room->room_code ?? 'N/A' }}

                                    </option>

                                @endforeach

                            </select>

                            @error('contract_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            {{-- KHÔNG CÓ HỢP ĐỒNG --}}

                            <div id="empty-contract" class="empty-contract">

                                <i class="mdi mdi-alert-outline me-1"></i>

                                Khách thuê này chưa có hợp đồng phù hợp để đăng ký tạm trú.

                            </div>


                            {{-- THÔNG TIN HỢP ĐỒNG --}}

                            <div id="contract-info" class="contract-preview d-none">

                                <div class="row g-3">

                                    <div class="col-md-4">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                Mã hợp đồng
                                            </span>

                                            <span id="contract-code" class="preview-value">
                                                -
                                            </span>

                                        </div>

                                    </div>


                                    <div class="col-md-4">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                Phòng
                                            </span>

                                            <span id="contract-room" class="preview-value">
                                                -
                                            </span>

                                        </div>

                                    </div>


                                    <div class="col-md-4">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                Trạng thái
                                            </span>

                                            <span class="badge bg-success-subtle text-success">

                                                <i class="mdi mdi-check-circle-outline me-1"></i>

                                                Hợp đồng hợp lệ

                                            </span>

                                        </div>

                                    </div>


                                    <div class="col-md-6">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                Ngày bắt đầu thuê
                                            </span>

                                            <span id="contract-start-date" class="preview-value">
                                                -
                                            </span>

                                        </div>

                                    </div>


                                    <div class="col-md-6">

                                        <div class="preview-item">

                                            <span class="preview-label">
                                                Ngày kết thúc thuê
                                            </span>

                                            <span id="contract-end-date" class="preview-value">
                                                -
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ====================================================
                    3. THỜI GIAN TẠM TRÚ
                    ===================================================== --}}
                    {{-- THỜI GIAN TẠM TRÚ --}}
                    <div class="form-card">

                        <div class="card-header">
                            <h5 class="section-title">
                                <span class="section-icon">
                                    <i class="mdi mdi-calendar-clock"></i>
                                </span>

                                Thời gian tạm trú
                            </h5>
                        </div>

                        <div class="card-body">

                            <div class="alert alert-info border-0 mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="mdi mdi-information-outline fs-5 me-2"></i>

                                    <div>
                                        <strong>Thời gian tạm trú được lấy theo hợp đồng thuê.</strong>

                                        <div class="small mt-1">
                                            Hệ thống tự động sử dụng ngày bắt đầu và ngày kết thúc
                                            của hợp đồng đã chọn.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Ngày bắt đầu
                                    </label>

                                   <input type="date"
       name="start_date"
       id="start_date"
       class="form-control"
       readonly>

                                </div>

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Ngày kết thúc
                                    </label>
<input type="date"
       name="end_date"
       id="end_date"
       class="form-control"
       readonly>

                                    <div class="form-text">
                                        Được lấy tự động từ hợp đồng.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ====================================================
                    4. THÔNG TIN ĐĂNG KÝ
                    ===================================================== --}}

                    <div class="form-card">

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

                                    <label for="status" class="form-label">
                                        Trạng thái
                                        <span class="required">*</span>
                                    </label>

                                    <select name="status" id="status"
                                        class="form-select @error('status') is-invalid @enderror" required>

                                        <option value="pending" @selected(old('status', 'active') === 'pending')>
                                            Chờ xác nhận
                                        </option>

                                        <option value="active" @selected(old('status', 'active') === 'active')>
                                            Đang tạm trú
                                        </option>

                                        <option value="expired" @selected(old('status') === 'expired')>
                                            Đã hết hạn
                                        </option>

                                        <option value="cancelled" @selected(old('status') === 'cancelled')>
                                            Đã hủy
                                        </option>

                                    </select>

                                    @error('status')

                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>

                                    @enderror

                                </div>


                                <div class="col-md-7">

                                    <label for="note" class="form-label">
                                        Ghi chú
                                    </label>

                                    <textarea name="note" id="note" rows="3" maxlength="1000"
                                        class="form-control @error('note') is-invalid @enderror"
                                        placeholder="Nhập ghi chú...">{{ old('note') }}</textarea>

                                    @error('note')

                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>

                                    @enderror

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ========================================================
                RIGHT
                ========================================================= --}}

                <div class="col-lg-4">

                    {{-- ====================================================
                    TÓM TẮT
                    ===================================================== --}}

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

                                <span id="summary-tenant" class="summary-value">
                                    Chưa chọn
                                </span>

                            </div>


                            <div class="summary-row">

                                <span class="summary-label">
                                    Phòng
                                </span>

                                <span id="summary-room" class="summary-value">
                                    Chưa chọn
                                </span>

                            </div>


                            <div class="summary-row">

                                <span class="summary-label">
                                    Hợp đồng
                                </span>

                                <span id="summary-contract" class="summary-value">
                                    Chưa chọn
                                </span>

                            </div>


                            <div class="summary-row">

                                <span class="summary-label">
                                    Thời gian
                                </span>

                                <span id="summary-date" class="summary-value">
                                    Chưa chọn
                                </span>

                            </div>

                        </div>

                    </div>


                    {{-- ====================================================
                    HƯỚNG DẪN
                    ===================================================== --}}

                    <div class="side-card">

                        <div class="side-card-header">

                            <i class="mdi mdi-help-circle-outline text-primary me-1"></i>

                            Quy trình đăng ký

                        </div>

                        <div class="side-card-body">

                            <div class="step-item">

                                <div class="step-number">
                                    1
                                </div>

                                <div class="step-content">

                                    <h6>
                                        Chọn khách thuê
                                    </h6>

                                    <p>
                                        Chọn đúng khách cần đăng ký tạm trú.
                                    </p>

                                </div>

                            </div>


                            <div class="step-item">

                                <div class="step-number">
                                    2
                                </div>

                                <div class="step-content">

                                    <h6>
                                        Chọn hợp đồng
                                    </h6>

                                    <p>
                                        Hợp đồng phải thuộc đúng khách thuê.
                                    </p>

                                </div>

                            </div>


                            <div class="step-item">

                                <div class="step-number">
                                    3
                                </div>

                                <div class="step-content">

                                    <h6>
                                        Tự động lấy thời gian
                                    </h6>

                                    <p>
                                        Thời gian tạm trú được lấy trực tiếp từ hợp đồng.
                                    </p>

                                </div>

                            </div>


                            <div class="step-item">

                                <div class="step-number">
                                    4
                                </div>

                                <div class="step-content">

                                    <h6>
                                        Kiểm tra và lưu
                                    </h6>

                                    <p>
                                        Kiểm tra thông tin trước khi tạo hồ sơ.
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ====================================================
                    ACTION
                    ===================================================== --}}

                    <div class="side-card action-card">

                        <div class="side-card-body">

                            <button type="submit" class="btn btn-primary btn-save w-100 mb-2">

                                <i class="mdi mdi-content-save-outline me-1"></i>

                                Lưu đăng ký tạm trú

                            </button>


                            <a href="{{ route('admin.temporary_residences.index') }}" class="btn btn-light w-100">

                                <i class="mdi mdi-close me-1"></i>

                                Hủy

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>
    ```

@endsection

@push('scripts')

<script>

    document.addEventListener('DOMContentLoaded', function () {

        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

        const tenantSelect =
            document.getElementById('tenant_id');

        const contractSelect =
            document.getElementById('contract_id');

        const tenantInfo =
            document.getElementById('tenant-info');

        const contractInfo =
            document.getElementById('contract-info');

        const emptyContract =
            document.getElementById('empty-contract');

        const tenantName =
            document.getElementById('tenant-name');

        const tenantPhone =
            document.getElementById('tenant-phone');

        const tenantCccd =
            document.getElementById('tenant-cccd');

        const tenantAddress =
            document.getElementById('tenant-address');

        const contractCode =
            document.getElementById('contract-code');

        const contractRoom =
            document.getElementById('contract-room');

        const contractStartDate =
            document.getElementById('contract-start-date');

        const contractEndDate =
            document.getElementById('contract-end-date');

        const summaryTenant =
            document.getElementById('summary-tenant');

        const summaryRoom =
            document.getElementById('summary-room');

        const summaryContract =
            document.getElementById('summary-contract');

        const summaryDate =
            document.getElementById('summary-date');

        const startDate =
            document.getElementById('start_date');

        const endDate =
            document.getElementById('end_date');


        /*
        |--------------------------------------------------------------------------
        | TENANTS
        |--------------------------------------------------------------------------
        */

        const tenants = {{ Js::from(
            $tenants->map(function ($tenant) {

                return [
                    'id' => $tenant->id,
                    'full_name' => $tenant->full_name,
                    'phone' => $tenant->phone,
                    'cccd' => $tenant->cccd,
                    'address' => $tenant->address,
                ];

            })->values()
        ) }};


        /*
        |--------------------------------------------------------------------------
        | CONTRACTS
        |--------------------------------------------------------------------------
        */

        const contracts = {{ Js::from(
            $contracts->map(function ($contract) {

                return [
                    'id' => $contract->id,

                    'tenant_id' =>
                        $contract->tenant_id,

                    'contract_code' =>
                        $contract->contract_code
                        ?? ('HD-' . $contract->id),

                    'room' =>
                        $contract->room->room_code
                        ?? 'N/A',

                    'start_date' =>
                        $contract->start_date
                        ? \Carbon\Carbon::parse(
                            $contract->start_date
                        )->format('Y-m-d')
                        : null,

                    'end_date' =>
                        $contract->end_date
                        ? \Carbon\Carbon::parse(
                            $contract->end_date
                        )->format('Y-m-d')
                        : null,
                ];

            })->values()
        ) }};


        /*
        |--------------------------------------------------------------------------
        | FORMAT DATE
        |--------------------------------------------------------------------------
        */

        function formatDate(dateString) {

            if (!dateString) {
                return 'Chưa xác định';
            }

            const parts =
                dateString.split('-');

            if (parts.length !== 3) {
                return dateString;
            }

            return (
                parts[2] +
                '/' +
                parts[1] +
                '/' +
                parts[0]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | RESET CONTRACT INFO
        |--------------------------------------------------------------------------
        */

        function resetContractInfo() {

            contractInfo.classList.add('d-none');

            contractCode.textContent = '-';

            contractRoom.textContent = '-';

            contractStartDate.textContent = '-';

            contractEndDate.textContent = '-';

            startDate.value = '';

            endDate.value = '';

            summaryContract.textContent =
                'Chưa chọn';

            summaryRoom.textContent =
                'Chưa chọn';

            summaryDate.textContent =
                'Chưa chọn';
        }


        /*
        |--------------------------------------------------------------------------
        | RESET TENANT INFO
        |--------------------------------------------------------------------------
        */

        function resetTenantInfo() {

            tenantInfo.classList.add('d-none');

            tenantName.textContent = '-';

            tenantPhone.textContent = '-';

            tenantCccd.textContent = '-';

            tenantAddress.textContent = '-';

            summaryTenant.textContent =
                'Chưa chọn';
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE TENANT
        |--------------------------------------------------------------------------
        */

        function updateTenant() {

            const tenantId =
                tenantSelect.value;

            if (!tenantId) {

                resetTenantInfo();

                contractSelect.dataset.selected = '';

                filterContracts();

                return;
            }


            const tenant =
                tenants.find(function (item) {

                    return String(item.id) ===
                        String(tenantId);

                });


            if (!tenant) {

                resetTenantInfo();

                return;
            }


            tenantInfo.classList.remove('d-none');


            tenantName.textContent =
                tenant.full_name || '-';

            tenantPhone.textContent =
                tenant.phone || '-';

            tenantCccd.textContent =
                tenant.cccd || '-';

            tenantAddress.textContent =
                tenant.address || '-';


            summaryTenant.textContent =
                tenant.full_name ||
                'Chưa chọn';


            filterContracts();
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER CONTRACTS
        |--------------------------------------------------------------------------
        */

        function filterContracts() {

            const tenantId =
                tenantSelect.value;

            const selectedContractId =
                contractSelect.dataset.selected || '';


            contractSelect.innerHTML =
                '<option value="">-- Chọn hợp đồng --</option>';


            emptyContract.style.display =
                'none';


            resetContractInfo();


            if (!tenantId) {
                return;
            }


            const tenantContracts =
                contracts.filter(function (contract) {

                    return String(contract.tenant_id) ===
                        String(tenantId);

                });


            if (tenantContracts.length === 0) {

                emptyContract.style.display =
                    'block';

                return;
            }


            tenantContracts.forEach(function (contract) {

                const option =
                    document.createElement('option');


                option.value =
                    contract.id;


                option.dataset.tenantId =
                    contract.tenant_id;


                option.dataset.room =
                    contract.room;


                option.dataset.contract =
                    contract.contract_code;


                /*
                |--------------------------------------------------------------------------
                | LƯU NGÀY HỢP ĐỒNG VÀO OPTION
                |--------------------------------------------------------------------------
                */

                option.dataset.startDate =
                    contract.start_date || '';


                option.dataset.endDate =
                    contract.end_date || '';


                option.textContent =
                    contract.contract_code +
                    ' — Phòng ' +
                    contract.room;


                if (
                    String(selectedContractId) ===
                    String(contract.id)
                ) {

                    option.selected = true;
                }


                contractSelect.appendChild(option);

            });


            updateContract();
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE CONTRACT
        |--------------------------------------------------------------------------
        */

        function updateContract() {

            const option =
                contractSelect.options[
                    contractSelect.selectedIndex
                ];


            if (!option || !option.value) {

                resetContractInfo();

                return;
            }


            contractInfo.classList.remove('d-none');


            const code =
                option.dataset.contract ||
                option.textContent;


            const room =
                option.dataset.room ||
                '-';


            const contractStart =
                option.dataset.startDate ||
                '';


            const contractEnd =
                option.dataset.endDate ||
                '';


            /*
            |--------------------------------------------------------------------------
            | CONTRACT PREVIEW
            |--------------------------------------------------------------------------
            */

            contractCode.textContent =
                code;


            contractRoom.textContent =
                room;


            contractStartDate.textContent =
                formatDate(contractStart);


            contractEndDate.textContent =
                formatDate(contractEnd);


            /*
            |--------------------------------------------------------------------------
            | CẬP NHẬT THỜI GIAN TẠM TRÚ THEO HỢP ĐỒNG
            |--------------------------------------------------------------------------
            */

            startDate.value =
                option.dataset.startDate || '';


            endDate.value =
                option.dataset.endDate || '';


            /*
            |--------------------------------------------------------------------------
            | SUMMARY
            |--------------------------------------------------------------------------
            */

            summaryContract.textContent =
                code;


            summaryRoom.textContent =
                'Phòng ' + room;


            updateSummaryDate();
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE SUMMARY DATE
        |--------------------------------------------------------------------------
        */

        function updateSummaryDate() {

            if (!startDate.value) {

                summaryDate.textContent =
                    'Chưa chọn';

                return;
            }


            const start =
                formatDate(startDate.value);


            if (endDate.value) {

                summaryDate.textContent =
                    start +
                    ' → ' +
                    formatDate(endDate.value);

                return;
            }


            summaryDate.textContent =
                start +
                ' → Không xác định';
        }


        /*
        |--------------------------------------------------------------------------
        | EVENTS
        |--------------------------------------------------------------------------
        */

        tenantSelect.addEventListener(
            'change',
            function () {

                contractSelect.dataset.selected =
                    '';

                updateTenant();
            }
        );


        contractSelect.addEventListener(
            'change',
            function () {

                contractSelect.dataset.selected =
                    contractSelect.value;

                updateContract();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | INITIALIZE
        |--------------------------------------------------------------------------
        */

        contractSelect.dataset.selected =
            @json(old('contract_id'));


        updateTenant();


        /*
        |--------------------------------------------------------------------------
        | RESTORE OLD CONTRACT
        |--------------------------------------------------------------------------
        */

        const oldContractId =
            @json(old('contract_id'));


        if (oldContractId) {

            contractSelect.dataset.selected =
                oldContractId;

            updateContract();
        }

    });

</script>

@endpush

