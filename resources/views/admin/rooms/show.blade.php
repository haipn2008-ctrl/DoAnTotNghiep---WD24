@extends('layouts.admin.home')

@section('content')

    <div class="container mt-4">

        <div class="card shadow-sm">

            <div class="card-header bg-info text-white">

                <h5 class="mb-0">
                    Chi tiết phòng
                </h5>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        @if($room->thumbnail)

                            <img src="{{ asset('storage/' . $room->thumbnail) }}" class="img-fluid rounded border">

                        @else

                            <img src="https://via.placeholder.com/400x250" class="img-fluid rounded border">

                        @endif

                    </div>

                    <div class="col-md-8">

                        <table class="table table-bordered">

                            <tr>
                                <th>Mã phòng</th>
                                <td>{{ $room->room_code }}</td>
                            </tr>

                            <tr>
                                <th>Tầng</th>
                                <td>{{ $room->floor }}</td>
                            </tr>

                            <tr>
                                <th>Giá thuê</th>
                                <td>{{ number_format($room->price) }} VNĐ</td>
                            </tr>

                            <tr>
                                <th>Diện tích</th>
                                <td>{{ $room->area }} m²</td>
                            </tr>

                            <tr>
                                <th>Số người hiện tại</th>
                                <td>
                                    {{ $room->current_people }}
                                    /
                                    {{ $room->max_people }}
                                </td>
                            </tr>

                            <tr>
                                <th>Trạng thái</th>
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
                            </tr>

                            <tr>
                                <th>Mô tả</th>
                                <td>{{ $room->description }}</td>
                            </tr>

                        </table>

                    </div>

                </div>

                <hr>

                <h5>Tiện ích phòng</h5>

                @forelse($room->amenities as $amenity)

                    <span class="badge bg-success me-1">
                        {{ $amenity->name }}
                    </span>

                @empty

                    <span class="text-muted">
                        Chưa có tiện ích
                    </span>

                @endforelse

                <div class="mt-4">

                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-secondary">

                        Quay lại

                    </a>

                </div>

            </div>

        </div>

    </div>

@endsection
