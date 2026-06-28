```blade
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

                            @error('room_code')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
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

                            @error('floor')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Diện tích (m²)
                            </label>

                            <input type="number" step="0.01" name="area" class="form-control"
                                value="{{ old('area', $room->area) }}">

                            @error('area')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                Giá thuê
                            </label>

                            <input type="number" name="price" class="form-control" value="{{ old('price', $room->price) }}">

                            @error('price')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-bold">
                                Số người hiện tại (Tối đa 4 người)
                            </label>

                            <input type="number" name="current_people" class="form-control"
                                value="{{ old('current_people', $room->current_people) }}">

                            @error('current_people')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

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

                            @error('status')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                        <div class="col-md-12 mb-3">

                            <label class="form-label fw-bold">
                                Tiện ích phòng
                            </label>

                            <div class="row">

                                @foreach($amenities as $amenity)

                                                        <div class="col-md-3 mb-2">

                                                            <label class="border rounded p-2 w-100">

                                                                <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}" {{
                                    in_array(
                                        $amenity->id,
                                        old(
                                            'amenities',
                                            $room->amenities
                                                ->pluck('id')
                                                ->toArray()
                                        )
                                    )
                                    ? 'checked'
                                    : ''
                                                                                                                                                                                                                       }}>

                                                                {{ $amenity->name }}

                                                            </label>

                                                        </div>

                                @endforeach

                            </div>

                        </div>

                        <div class="col-md-12 mb-3">

                            <label class="form-label fw-bold">
                                Ảnh phòng
                            </label>

                            <input type="file" name="image" class="form-control">

                            @if($room->thumbnail)

                                <div class="mt-2">

                                    <img src="{{ asset('storage/' . $room->thumbnail) }}" width="150" class="img-thumbnail">

                                </div>

                            @endif

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
```
