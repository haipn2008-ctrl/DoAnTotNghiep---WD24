@extends('layouts.admin.home')

@section('content')
    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-white fw-bold">Thêm khách thuê mới</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.tenants.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold">Họ và tên</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Nhập họ và tên"
                            value="{{ old('full_name') }}">
                        @error('full_name')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">CCCD</label>
                        <input type="text" name="cccd" class="form-control" placeholder="Nhập số CCCD"
                            value="{{ old('cccd') }}">
                        @error('cccd')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" placeholder="Nhập số điện thoại"
                            value="{{ old('phone') }}">
                        @error('phone')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Nhập địa chỉ email"
                            value="{{ old('email') }}">
                        @error('email')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="3"
                            placeholder="Nhập địa chỉ tạm trú/thường trú">{{ old('address') }}</textarea>
                        @error('address')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Thêm khách thuê
                        </button>
                        <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary px-4 ms-2">
                            Quay lại
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
