@extends('layouts.admin.home')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Xuất danh sách phòng</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.rooms.index') }}">Phòng</a></li>
                            <li class="breadcrumb-item active">Xuất danh sách phòng</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body bg-light">
                <div class="row">
                    <div class="col-md-8 align-self-center">
                        <p class="mb-0">
                            Trang xuất danh sách phòng chỉ dùng để tải file CSV toàn bộ phòng hiện tại.
                        </p>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.rooms.export.download') }}" class="btn btn-success px-4 py-2">
                            <i class="fas fa-file-csv me-1"></i> Xuất file CSV
                        </a>
                        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary px-4 py-2">
                            Quay lại danh sách phòng
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="text-white" style="background-color:#4e73df;">
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã phòng</th>
                                        <th>Tầng</th>
                                        <th>Giá thuê</th>
                                        <th>Diện tích</th>
                                        <th>Số người</th>
                                        <th>Trạng thái</th>
                                        <th>Tiện ích</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rooms as $room)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $room->room_code }}</td>
                                            <td>{{ $room->floor }}</td>
                                            <td>{{ number_format($room->price) }} VNĐ</td>
                                            <td>{{ $room->area }} m²</td>
                                            <td>{{ $room->current_people }} người</td>
                                            <td>
                                                @if($room->status == 'available')
                                                    <span class="badge bg-success">Trống</span>
                                                @elseif($room->status == 'occupied')
                                                    <span class="badge bg-danger">Đang thuê</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Bảo trì</span>
                                                @endif
                                            </td>
                                            <td>{{ $room->amenities->pluck('name')->join(', ') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">Không có phòng để xuất</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            {{ $rooms->links() }}
        </div>
    </div>
@endsection
