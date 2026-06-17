<h2>Cập nhật phòng</h2>

<form action="{{ route('admin.rooms.update', $room->id) }}" method="POST">

    @csrf
    @method('PUT')

    <div>
        <label>Mã phòng</label>
        <input
            type="text"
            name="room_code"
            value="{{ old('room_code', $room->room_code) }}">
    </div>

    <div>
        <label>Giá thuê</label>
        <input
            type="number"
            name="price"
            value="{{ old('price', $room->price) }}">
    </div>

    <div>
        <label>Diện tích</label>
        <input
            type="number"
            name="area"
            value="{{ old('area', $room->area) }}">
    </div>

    <div>
        <label>Trạng thái</label>

        <select name="status">

            <option value="available"
                {{ $room->status == 'available' ? 'selected' : '' }}>
                Trống
            </option>

            <option value="occupied"
                {{ $room->status == 'occupied' ? 'selected' : '' }}>
                Đang thuê
            </option>

            <option value="maintenance"
                {{ $room->status == 'maintenance' ? 'selected' : '' }}>
                Bảo trì
            </option>

        </select>
    </div>

    <button type="submit">
        Cập nhật
    </button>

</form>
