@extends('layouts.admin.home')

@section('content')

    <div class="container mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-warning">
                <h5 class="mb-0 fw-bold text-dark">
                    Cập nhật phòng
                </h5>
            </div>

            <div class="card-body">

                <form action="{{ route('admin.rooms.update', $room->id) }}" method="POST" enctype="multipart/form-data">

                    @csrf
                    @method('PUT')

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Mã phòng
                            </label>

                            <input type="text" name="room_code" class="form-control"
                                value="{{ old('room_code', $room->room_code) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Tầng
                            </label>

                            <select name="floor" class="form-select">

                                @for($i = 1; $i <= 5; $i++)

                                    <option value="{{ $i }}" {{ old('floor', $room->floor) == $i ? 'selected' : '' }}>
                                        Tầng {{ $i }}
                                    </option>

                                @endfor

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Loại phòng
                            </label>

                            <select name="room_type" class="form-select">

                                <option value="standard" {{ old('room_type', $room->room_type) == 'standard' ? 'selected' : '' }}>
                                    Thường
                                </option>

                                <option value="vip" {{ old('room_type', $room->room_type) == 'vip' ? 'selected' : '' }}>
                                    VIP
                                </option>

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Trạng thái
                            </label>

                            <select name="status" class="form-select">

                                <option value="available" {{ old('status', $room->status) == 'available' ? 'selected' : '' }}>
                                    Trống
                                </option>

                                <option value="occupied" {{ old('status', $room->status) == 'occupied' ? 'selected' : '' }}>
                                    Đang thuê
                                </option>

                                <option value="maintenance" {{ old('status', $room->status) == 'maintenance' ? 'selected' : '' }}>
                                    Bảo trì
                                </option>

                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Giá thuê
                            </label>

                            <input type="number" name="price" class="form-control" value="{{ old('price', $room->price) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Diện tích (m²)
                            </label>

                            <input type="number" step="0.01" name="area" class="form-control"
                                value="{{ old('area', $room->area) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Số người tối đa
                            </label>

                            <input type="number" name="max_people" class="form-control"
                                value="{{ old('max_people', $room->max_people) }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Ảnh phòng
                            </label>

                            <input type="file" name="image" class="form-control">
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">
                                Mô tả
                            </label>

                            <textarea name="description" rows="4"
                                class="form-control">{{ old('description', $room->description) }}</textarea>
                        </div>

                    </div>

                    <div class="mt-3">

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i>
                            Cập nhật
                        </button>

                        <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary">
                            Quay lại
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection
