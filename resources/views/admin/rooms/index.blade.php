@extends('layouts.admin.home')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800 fw-bold">Danh sách phòng</h2>
        <a href="{{ route('admin.rooms.create') }}" class="btn text-white px-3 py-2 shadow-sm" style="background-color: #4e73df;">
            <i class="fas fa-plus-circle me-1"></i> Thêm phòng mới
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body bg-light rounded p-3">
            <form method="GET" action="{{ route('admin.rooms.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="room_code" class="form-control" placeholder="Nhập mã phòng cần tìm..." value="{{ request('room_code') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Trống</option>
                        <option value="occupied" {{ request('status') == 'occupied' ? 'selected' : '' }}>Đang thuê</option>
                        <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Bảo trì</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button type="submit" class="btn text-white px-3" style="background-color: #4e73df;">
                        <i class="fas fa-search me-1"></i> Tìm kiếm
                    </button>
                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary px-3">
                        <i class="fas fa-undo me-1"></i> Làm mới
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm custom-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-white" style="background-color: #4e73df;">
                        <tr>
                            <th class="ps-3 py-3" width="8%">STT</th>
                            <th class="py-3" width="18%">Mã phòng</th>
                            <th class="py-3" width="20%">Giá thuê</th>
                            <th class="py-3" width="15%">Diện tích</th>
                            <th class="py-3" width="18%">Trạng thái</th>
                            <th class="text-center py-3" width="21%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                        <tr>
                            <td class="ps-3 fw-bold text-secondary">{{ $loop->iteration }}</td>
                            <td class="fw-bold text-dark">
                                <span class="badge bg-purple-light text-primary px-2 py-2 fs-6">{{ $room->room_code }}</span>
                            </td>
                            <td class="fw-semibold text-danger">
                                {{ number_format($room->price) }} <small>VNĐ</small>
                            </td>
                            <td>{{ $room->area }} m²</td>
                            <td>
                                @if($room->status == 'available')
                                    <span class="badge bg-success-light text-success px-3 py-2 rounded-pill"><i class="fas fa-check me-1"></i> Trống</span>
                                @elseif($room->status == 'occupied')
                                    <span class="badge bg-danger-light text-danger px-3 py-2 rounded-pill"><i class="fas fa-user me-1"></i> Đang thuê</span>
                                @else
                                    <span class="badge bg-warning-light text-warning px-3 py-2 rounded-pill"><i class="fas fa-tools me-1"></i> Bảo trì</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-sm text-white px-2 py-1" style="background-color: #f6c23e;" title="Sửa">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>

                                    <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm text-white px-2 py-1" style="background-color: #e74a3b;" title="Xóa">
                                            <i class="fas fa-trash-alt"></i> Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-search-minus fa-2x mb-2 d-block text-gray-300"></i>
                                <p class="mb-0">Không tìm thấy phòng nào phù hợp với điều kiện lọc.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        {{ $rooms->appends(request()->query())->links() }}
    </div>
</div>

<style>
    .custom-table-card {
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.03);
    }
    /* Style riêng cho các badge trạng thái có background màu pastel nhạt dịu mắt */
    .bg-success-light { background-color: rgba(28, 200, 138, 0.15) !important; }
    .bg-danger-light { background-color: rgba(231, 74, 59, 0.15) !important; }
    .bg-warning-light { background-color: rgba(246, 194, 62, 0.15) !important; }
    .bg-purple-light { background-color: rgba(78, 115, 223, 0.1) !important; }
</style>
@endsection
