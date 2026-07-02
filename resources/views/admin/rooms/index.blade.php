@extends('layouts.admin.home')

@section('content')

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h2 class="h3 mb-0 text-gray-800 fw-bold">
                Danh sách phòng
            </h2>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.rooms.export', request()->only(['room_code', 'status'])) }}"
                    class="btn btn-success px-3 py-2 shadow-sm">
                    <i class="fas fa-file-csv me-1"></i>
                    Xuất danh sách
                </a>

                <a href="{{ route('admin.rooms.create') }}" class="btn text-white px-3 py-2 shadow-sm"
                    style="background-color:#4e73df;">
                    <i class="fas fa-plus-circle me-1"></i>
                    Thêm phòng mới
                </a>
            </div>

        </div>

        @if(session('success'))

            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">

                <i class="fas fa-check-circle me-2"></i>

                {{ session('success') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>

            </div>

        @endif

        @if(session('error'))

            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">

                <i class="fas fa-exclamation-triangle me-2"></i>

                {{ session('error') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>

            </div>

        @endif
        <div class="card border-0 shadow-sm mb-4">

            <div class="card-body bg-light">

                <form method="GET" action="{{ route('admin.rooms.index') }}" class="row g-2">

                    <div class="col-md-4">

                        <input type="text" name="room_code" class="form-control" placeholder="Nhập mã phòng..."
                            value="{{ request('room_code') }}">

                    </div>

                    <div class="col-md-3">

                        <select name="status" class="form-select">

                            <option value="">
                                -- Tất cả trạng thái --
                            </option>

                            <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>
                                Trống
                            </option>

                            <option value="occupied" {{ request('status') == 'occupied' ? 'selected' : '' }}>
                                Đang thuê
                            </option>

                            <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>
                                Bảo trì
                            </option>

                        </select>

                    </div>

                    <div class="col-md-5 d-flex gap-2">

                        <button class="btn text-white" style="background-color:#4e73df;">
                            <i class="fas fa-search"></i>
                            Tìm kiếm
                        </button>

                        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
                            Làm mới
                        </a>

                    </div>

                </form>

            </div>

        </div>

        <div class="card border-0 shadow-sm custom-table-card">

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="text-white" style="background-color:#4e73df;">

                            <tr>

                                <th>STT</th>

                                <th>Ảnh</th>

                                <th>Mã phòng</th>

                                <th>Tầng</th>

                                <th>Giá thuê</th>

                                <th>Diện tích</th>

                                <th>Số người hiện tại</th>

                                <th>Trạng thái</th>

                                <th class="text-center">
                                    Thao tác
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($rooms as $room)

                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        @if($room->thumbnail)

                                            <img src="{{ asset('storage/' . $room->thumbnail) }}" width="70" height="70"
                                                class="rounded object-fit-cover">

                                        @else

                                            <span class="text-muted">
                                                Chưa có ảnh
                                            </span>

                                        @endif
                                    </td>
                                    <td class="fw-bold">
                                        {{ $room->room_code }}
                                    </td>

                                    <td>
                                        Tầng {{ $room->floor }}
                                    </td>

                                    <td class="text-danger fw-bold">
                                        {{ number_format($room->price) }} VNĐ
                                    </td>

                                    <td>
                                        {{ $room->area }} m²
                                    </td>

                                    <td>
                                        {{ $room->current_people }} người
                                    </td>

                                    <td>

                                        @if($room->status == 'available')

                                            <span class="badge bg-success">
                                                Trống
                                            </span>

                                        @elseif($room->status == 'occupied')

                                            <span class="badge bg-danger">
                                                Đang thuê
                                            </span>

                                        @else

                                            <span class="badge bg-warning text-dark">
                                                Bảo trì
                                            </span>

                                        @endif

                                    </td>

                                    <td class="text-center">

                                        <a href="{{ route('admin.rooms.show', $room->id) }}"
                                            class="btn btn-info btn-sm text-white">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>

                                        </a>

                                        <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST"
                                            class="d-inline">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Bạn có chắc muốn xóa phòng này?')">

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="10" class="text-center py-5">

                                        Chưa có phòng nào

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        <div class="mt-4 d-flex justify-content-end">
            {{ $rooms->links() }}
        </div>

    </div>

    <style>
        .custom-table-card {
            border-radius: .5rem;
            overflow: hidden;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, .03);
        }

        .object-fit-cover {
            object-fit: cover;
        }
    </style>

@endsection
