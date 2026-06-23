@extends('layouts.admin.home')

@section('content')

<div class="container mt-4">
    <div class="card border-0 shadow-sm custom-card">
        <div class="card-header text-white" style="background-color: #4e73df;">
            <h5 class="mb-0 text-white fw-bold">
                Thêm khách thuê mới
            </h5>
        </div>

    <div class="card-body p-4">
        <form action="{{ route('admin.tenants.store') }}" method="POST">
            @csrf

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-secondary">
                        Họ và tên
                    </label>

                    <input type="text"
                           name="full_name"
                           class="form-control"
                           placeholder="Nguyễn Văn A"
                           value="{{ old('full_name') }}">

                    @error('full_name')
                        <small class="text-danger d-block mt-1">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-secondary">
                        Ngày sinh
                    </label>

                    <input type="date"
                           name="date_of_birth"
                           class="form-control"
                           value="{{ old('date_of_birth') }}">

                    @error('date_of_birth')
                        <small class="text-danger d-block mt-1">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-secondary">
                        CCCD
                    </label>

                    <input type="text"
                           name="cccd"
                           class="form-control"
                           placeholder="012345678901"
                           value="{{ old('cccd') }}">

                    @error('cccd')
                        <small class="text-danger d-block mt-1">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-secondary">
                        Ngày cấp CCCD
                    </label>

                    <input type="date"
                           name="cccd_issue_date"
                           class="form-control"
                           value="{{ old('cccd_issue_date') }}">

                    @error('cccd_issue_date')
                        <small class="text-danger d-block mt-1">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">
                    Nơi cấp CCCD
                </label>

                <input type="text"
                       name="cccd_issue_place"
                       class="form-control"
                       placeholder="Cục Cảnh sát QLHC về TTXH"
                       value="{{ old('cccd_issue_place') }}">

                @error('cccd_issue_place')
                    <small class="text-danger d-block mt-1">
                        {{ $message }}
                    </small>
                @enderror
            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-secondary">
                        Số điện thoại
                    </label>

                    <input type="text"
                           name="phone"
                           class="form-control"
                           placeholder="0366xxxxxx"
                           value="{{ old('phone') }}">

                    @error('phone')
                        <small class="text-danger d-block mt-1">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold text-secondary">
                        Email
                    </label>

                    <input type="email"
                           name="email"
                           class="form-control"
                           placeholder="example@gmail.com"
                           value="{{ old('email') }}">

                    @error('email')
                        <small class="text-danger d-block mt-1">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary">
                    Địa chỉ
                </label>

                <textarea name="address"
                          rows="3"
                          class="form-control"
                          placeholder="Nhập địa chỉ">{{ old('address') }}</textarea>

                @error('address')
                    <small class="text-danger d-block mt-1">
                        {{ $message }}
                    </small>
                @enderror
            </div>

            <div class="mt-4 pt-2">
                <button type="submit"
                        class="btn text-white px-4"
                        style="background-color: #4e73df;">
                    <i class="fas fa-save me-1"></i>
                    Thêm khách thuê
                </button>

                <a href="{{ route('admin.tenants.index') }}"
                   class="btn btn-secondary px-4 ms-2">
                    Quay lại
                </a>
            </div>

        </form>
    </div>
</div>

</div>

<style>
    .custom-card {
        border-radius: 0.5rem;
        overflow: hidden;
    }
</style>

@endsection
