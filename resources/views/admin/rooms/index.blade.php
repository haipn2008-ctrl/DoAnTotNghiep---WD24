@extends('layouts.admin.home')

@section('content')

    <h2>Danh sách phòng</h2>

    <a href="{{ route('admin.rooms.create') }}">
        Thêm phòng
    </a>

    <br><br>

    @if(session('success'))
        <div>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div>
            {{ session('error') }}
        </div>
    @endif
    {{-- Tìm kiếm --}}
    <form method="GET" action="{{ route('admin.rooms.index') }}" style="margin-bottom:20px">

        <input type="text" name="room_code" placeholder="Nhập mã phòng" value="{{ request('room_code') }}">

        <select name="status">

            <option value="">
                -- Chọn trạng thái --
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

        <button type="submit">
            Tìm kiếm
        </button>

        <a href="{{ route('admin.rooms.index') }}">
            Làm mới
        </a>

    </form>
    {{-- Bảng --}}
    <table border="1" width="100%" cellpadding="10">

        <thead>
            <tr>
                <th>STT</th>
                <th>Mã phòng</th>
                <th>Giá thuê</th>
                <th>Diện tích</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>

        <tbody>

            @forelse($rooms as $room)

                <tr>

                    <td>{{ $loop->iteration }}</td>

                    <td>{{ $room->room_code }}</td>

                    <td>
                        {{ number_format($room->price) }} VNĐ
                    </td>

                    <td>
                        {{ $room->area }} m²
                    </td>

                    <td>

                        @if($room->status == 'available')
                            Trống
                        @elseif($room->status == 'occupied')
                            Đang thuê
                        @else
                            Bảo trì
                        @endif

                    </td>

                    <td>

                        <a href="{{ route('admin.rooms.edit', $room->id) }}">
                            Sửa
                        </a>

                        <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST" style="display:inline">

                            @csrf
                            @method('DELETE')

                            <button onclick="return confirm('Bạn có chắc muốn xóa?')">

                                Xóa

                            </button>

                        </form>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="6">
                        Không có phòng như bạn mong muốn tìm
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

    <br>

    {{ $rooms->appends(request()->query())->links() }}
@endsection
