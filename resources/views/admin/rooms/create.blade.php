<form action="{{ route('admin.rooms.store') }}" method="POST">
    @csrf

    <div>
        <label>Mã phòng</label>
        <input type="text" name="room_code">
    </div>

    <div>
        <label>Giá thuê</label>
        <input type="number" name="price">
    </div>

    <div>
        <label>Diện tích</label>
        <input type="number" name="area">
    </div>

    <div>
        <label>Trạng thái</label>

        <select name="status">
            <option value="available">Trống</option>
            <option value="occupied">Đang thuê</option>
            <option value="maintenance">Bảo trì</option>
        </select>
    </div>

    <button type="submit">
        Thêm phòng
    </button>
</form>
