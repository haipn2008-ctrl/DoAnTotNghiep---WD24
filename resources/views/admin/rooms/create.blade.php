@extends('layouts.admin.home')

@section('content')

    <div class="container mt-4">

        <div class="card border-0 shadow-sm custom-card">

            <div class="card-header text-white" style="background-color:#4e73df;">
                <h5 class="mb-0 text-white fw-bold">
                    Thêm phòng mới
                </h5>
            </div>

            <div class="card-body p-4">

                <form action="{{ route('admin.rooms.store') }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    <div class="row">

                        <div class="col-md-6">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    Mã phòng
                                </label>

                                <input type="text" name="room_code" class="form-control" value="{{ old('room_code') }}"
                                    placeholder="P101">

                                @error('room_code')
                                    <small class="text-danger d-block mt-1">
                                        {{ $message }}
                                    </small>
                                @enderror
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    Tầng
                                </label>

                                <select name="floor" class="form-select">
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}">
                                            Tầng {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    Loại phòng
                                </label>

                                <select name="room_type" class="form-select">

                                    <option value="standard">
                                        Thường
                                    </option>

                                    <option value="vip">
                                        VIP
                                    </option>

                                </select>
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    Trạng thái
                                </label>

                                <select name="status" class="form-select">

                                    <option value="available">
                                        Trống
                                    </option>

                                    <option value="occupied">
                                        Đang thuê
                                    </option>

                                    <option value="maintenance">
                                        Bảo trì
                                    </option>

                                </select>
                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    Giá thuê (VNĐ)
                                </label>

                                <input type="number" name="price" class="form-control" value="{{ old('price') }}">
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    Diện tích (m²)
                                </label>

                                <input type="number" step="0.01" name="area" class="form-control" value="{{ old('area') }}">
                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    Số người tối đa
                                </label>

                                <input type="number" name="max_people" class="form-control"
                                    value="{{ old('max_people', 4) }}">
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">
                                    Số người hiện tại
                                </label>

                                <input type="number" name="current_people" class="form-control"
                                    value="{{ old('current_people', 0) }}">
                            </div>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold text-secondary">
                            Ảnh phòng
                        </label>

                        <input type="file" name="image" class="form-control">

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold text-secondary">
                            Mô tả
                        </label>

                        <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>

                    </div>

                    <div class="mt-4">

                        <button type="submit" class="btn text-white px-4" style="background-color:#4e73df;">
                            Thêm phòng
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
            border-radius: .5rem;
            overflow: hidden;
        }
    </style>

@endsection
