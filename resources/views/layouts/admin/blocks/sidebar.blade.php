<div class="vertical-menu">
    <div class="h-100" data-simplebar>
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Stay Master</li>
                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="home"></i>
                        <span>Tổng quan</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="#">Biểu đồ doanh thu tháng/năm</a></li>
                        <li><a href="#">Thống kê tổng doanh thu</a></li>
                        <li><a href="#">Thống kê số phòng</a></li>
                        <li><a href="#">Tỷ lệ lấp đầy</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="grid"></i>
                        <span>Quản lý Phòng</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="#">Danh sách phòng</a></li>
                        <li><a href="#">Thêm phòng mới</a></li>
                        <li><a href="#">Tìm kiếm phòng</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="users"></i>
                        <span>Khách thuê</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="#">Danh sách khách thuê</a></li>
                        <li><a href="#">Thêm khách thuê</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="file-text"></i>
                        <span>Hợp đồng</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="#">Tạo hợp đồng thuê</a></li>
                        <li><a href="#">Danh sách hợp đồng</a></li>
                        <li><a href="#">Gia hạn hợp đồng</a></li>
                        <li><a href="#">Kết thúc hợp đồng</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="zap"></i>
                        <span>Điện nước & Dịch vụ</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="{{ route('admin.utilities.create') }}">Nhập chỉ số điện/nước</a></li>
                        <li><a href="{{ route('admin.utilities.index') }}">Kiểm tra chỉ số</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="credit-card"></i>
                        <span>Hóa đơn & Công nợ</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="#">Sinh hóa đơn</a></li>
                        <li><a href="#">Danh sách hóa đơn</a></li>
                        <li><a href="#">Thanh toán hóa đơn</a></li>
                        <li><a href="#">Theo dõi công nợ</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="download"></i>
                        <span>Báo cáo & Xuất file</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
                        <li><a href="#">Xuất danh sách phòng</a></li>
                        <li><a href="#">Xuất danh sách khách thuê</a></li>
                        <li><a href="#">Xuất danh sách hóa đơn</a></li>
                        <li><a href="#">Xuất danh sách thanh toán</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript: void(0);">
                        <i data-feather="settings"></i>
                        <span>Hệ thống & Cài đặt</span>
                    </a>
                    <ul aria-expanded="false" class="sub-menu">
<<<<<<< Updated upstream
                        <li><a href="#">Quản lý tài khoản Admin/User</a></li>
                        <li><a href="#">Phân quyền</a></li>
=======
                        <li><a href="{{ route('admin.users.index') }}">Quản lý tài khoản Admin/User</a></li>
                        <li><a href="{{ route('admin.roles') }}">Phân quyền</a></li>
>>>>>>> Stashed changes
                        <li><a href="#">Cập nhật đơn giá điện</a></li>
                        <li><a href="#">Cập nhật đơn giá nước</a></li>
                        <li><a href="#">Cập nhật đơn giá internet</a></li>
                        <li><a href="#">Cập nhật đơn giá dịch vụ</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>