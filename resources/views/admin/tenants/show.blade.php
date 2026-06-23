@extends('layouts.admin.home')

@section('content')

<div class="container mt-4">

<div class="card border-0 shadow-sm custom-card">

    <div class="card-header text-white"
         style="background-color:#4e73df;">
        <h5 class="mb-0 text-white fw-bold">
            Chi tiết khách thuê
        </h5>
    </div>

    <div class="card-body p-4">

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="fw-bold text-secondary">
                    Họ và tên
                </label>
                <div>
                    {{ $tenant->full_name }}
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="fw-bold text-secondary">
                    Ngày sinh
                </label>
                <div>
                    {{ $tenant->date_of_birth ? \Carbon\Carbon::parse($tenant->date_of_birth)->format('d/m/Y') : 'Chưa cập nhật' }}
                </div>
            </div>

        </div>

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="fw-bold text-secondary">
                    CCCD
                </label>
                <div>
                    {{ $tenant->cccd }}
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="fw-bold text-secondary">
                    Ngày cấp CCCD
                </label>
                <div>
                    {{ $tenant->cccd_issue_date ? \Carbon\Carbon::parse($tenant->cccd_issue_date)->format('d/m/Y') : 'Chưa cập nhật' }}
                </div>
            </div>

        </div>

        <div class="mb-3">
            <label class="fw-bold text-secondary">
                Nơi cấp CCCD
            </label>
            <div>
                {{ $tenant->cccd_issue_place ?? 'Chưa cập nhật' }}
            </div>
        </div>

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="fw-bold text-secondary">
                    Số điện thoại
                </label>
                <div>
                    {{ $tenant->phone }}
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="fw-bold text-secondary">
                    Email
                </label>
                <div>
                    {{ $tenant->email ?? 'Chưa cập nhật' }}
                </div>
            </div>

        </div>

        <div class="mb-3">
            <label class="fw-bold text-secondary">
                Địa chỉ
            </label>
            <div>
                {{ $tenant->address ?? 'Chưa cập nhật' }}
            </div>
        </div>

        <hr>

        <div class="row">

            <div class="col-md-6">
                <label class="fw-bold text-secondary">
                    Ngày tạo hồ sơ
                </label>
                <div>
                    {{ $tenant->created_at->format('d/m/Y H:i') }}
                </div>
            </div>

            <div class="col-md-6">
                <label class="fw-bold text-secondary">
                    Cập nhật lần cuối
                </label>
                <div>
                    {{ $tenant->updated_at->format('d/m/Y H:i') }}
                </div>
            </div>

        </div>

        <div class="mt-4">

            <a href="{{ route('admin.tenants.edit', $tenant->id) }}"
               class="btn btn-warning">
                <i class="fas fa-edit me-1"></i>
                Chỉnh sửa
            </a>

            <a href="{{ route('admin.tenants.index') }}"
               class="btn btn-secondary ms-2">
                Quay lại
            </a>

        </div>

    </div>

</div>

</div>

<style>
    .custom-card{
        border-radius: 0.5rem;
        overflow: hidden;
    }

    label{
        display:block;
        margin-bottom:5px;
    }
</style>

@endsection
