<form action="{{ route('admin.tenants.store') }}"
      method="POST">

    @csrf

    <input type="text"
           name="full_name"
           placeholder="Họ tên">

    <input type="text"
           name="cccd"
           placeholder="CCCD">

    <input type="text"
           name="phone"
           placeholder="Số điện thoại">

    <input type="email"
           name="email"
           placeholder="Email">

    <input type="text"
           name="address"
           placeholder="Địa chỉ">

    <button type="submit">
        Thêm khách thuê
    </button>

</form>
