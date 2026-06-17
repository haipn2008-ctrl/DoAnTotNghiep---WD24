@extends('layouts.admin.home')

@section('content')
<div class="container mt-4">
    <div class="card border-0 shadow-sm custom-card">
        <div class="card-header text-white" style="background-color: #4e73df;">
            <h5 class="mb-0 text-white fw-bold">Thêm phòng mới</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.rooms.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary">Mã phòng</label>
                    <input type="text" name="room_code" class="form-control" placeholder="Ví dụ: P101, P102" value="{{ old('room_code') }}">
                    @error('room_code')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary">Giá thuê (VNĐ)</label>
                    <input type="number" name="price" class="form-control" placeholder="Nhập giá thuê" value="{{ old('price') }}">
                    @error('price')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary">Diện tích (m²)</label>
                    <input type="number" name="area" class="form-control" placeholder="Nhập diện tích phòng" value="{{ old('area') }}">
                    @error('area')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-secondary">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Trống</option>
                        <option value="occupied" {{ old('status') == 'occupied' ? 'selected' : '' }}>Đang thuê</option>
                        <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Bảo trì</option>
                    </select>
                    @error('status')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mt-4 pt-2">
                    <button type="submit" class="btn text-white px-4" style="background-color: #4e73df;">
                        <i class="fas fa-save me-1"></i> Thêm phòng
                    </button>
                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary px-4 ms-2">
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
