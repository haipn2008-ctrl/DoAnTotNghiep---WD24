# BÁO CÁO KIỂM THỬ HỆ THỐNG STAY MASTER

## 1. Phạm vi và môi trường kiểm thử

- Hệ thống: quản lý phòng trọ Stay Master.
- Công nghệ: Laravel 13, PHP 8.3+, Blade, Vite, MySQL/MariaDB.
- Loại kiểm thử hiện có: Unit/Feature test bằng PHPUnit; kiểm tra route, build giao diện và cấu hình Composer.
- Cơ sở dữ liệu khi chạy test: SQLite in-memory theo `phpunit.xml`, không sử dụng dữ liệu thật.
- Ngày thực hiện: 24/08/2026.

## 2. Kết quả tổng hợp

| Hạng mục | Kết quả |
| --- | --- |
| PHPUnit | 243/243 test đạt; PHPUnit đánh dấu 2 test `risky` |
| Số assertion | 2.263 |
| Thời gian PHPUnit | 25,7 giây |
| Route ứng dụng | 153 route |
| Build Vite | Đạt |
| Composer validate | Đạt |

Kết luận tại thời điểm kiểm thử: toàn bộ kiểm thử tự động hiện có đều đạt, route được nạp thành công và tài nguyên frontend build thành công.

## 3. Quy ước test case

- `P`: Positive test — dữ liệu và thao tác hợp lệ.
- `N`: Negative test — dữ liệu hoặc quyền truy cập không hợp lệ.
- Trạng thái `Passed` trong bảng được đối chiếu với test tự động đã chạy.
- Mỗi test case cần có ảnh chụp hoặc log chạy test nếu báo cáo yêu cầu bằng chứng.

## 4. Danh sách test case tiêu biểu

| Mã TC | Phân hệ | Loại | Tiền điều kiện / dữ liệu | Các bước thực hiện | Kết quả mong đợi | Trạng thái |
| --- | --- | --- | --- | --- | --- | --- |
| TC-AUTH-01 | Đăng nhập | P | Tài khoản admin đang hoạt động | Mở trang đăng nhập; nhập đúng email và mật khẩu; nhấn Đăng nhập | Đăng nhập thành công và chuyển đến trang quản trị | Passed |
| TC-AUTH-02 | Đăng nhập | N | Có tài khoản hợp lệ | Nhập đúng email nhưng sai mật khẩu | Hiện lỗi xác thực; không tạo phiên đăng nhập | Passed |
| TC-AUTH-03 | Đăng nhập | N | Tài khoản bị khóa hoặc ngừng hoạt động | Đăng nhập bằng tài khoản đó | Từ chối đăng nhập; dữ liệu kiểm toán không bị cập nhật sai | Passed |
| TC-AUTH-04 | Bảo mật đăng nhập | N | Chưa đăng nhập | Gửi yêu cầu trực tiếp đến URL được bảo vệ | Chuyển đến trang đăng nhập; không truy cập được dữ liệu | Passed |
| TC-AUTH-05 | Bảo mật đăng nhập | N | Có thông tin đăng nhập sai | Đăng nhập sai liên tiếp vượt giới hạn | Hệ thống giới hạn tần suất đăng nhập | Passed |
| TC-ACT-01 | Kích hoạt tài khoản | P | Khách thuê ở trạng thái chờ kích hoạt | Đăng nhập; đổi mật khẩu; chấp nhận điều khoản; xác nhận | Tài khoản được kích hoạt và tạo hồ sơ khách thuê cơ bản | Passed |
| TC-ACT-02 | Kích hoạt tài khoản | N | Tài khoản chờ kích hoạt | Dùng lại mật khẩu tạm làm mật khẩu mới | Hệ thống báo lỗi và không kích hoạt | Passed |
| TC-RBAC-01 | Phân quyền | N | Đăng nhập vai trò khách thuê | Truy cập trực tiếp URL `/admin/...` | Trả về 403; dữ liệu không thay đổi | Passed |
| TC-RBAC-02 | Phân quyền | N | Đăng nhập vai trò admin | Truy cập trực tiếp URL `/client/...` | Trả về 403; không vào cổng khách thuê | Passed |
| TC-RBAC-03 | Quyền hợp đồng | N | Người thuê thành viên có tài khoản riêng | Mở danh sách, truy cập trực tiếp hợp đồng và gửi yêu cầu thay đổi thành viên | Không nhìn thấy hoặc quản lý hợp đồng; chỉ người đại diện được thao tác | Passed |
| TC-USER-01 | Tài khoản | P | Admin đã đăng nhập | Tạo tài khoản với email chưa tồn tại và vai trò hợp lệ | Tạo thành công, trạng thái vòng đời đúng theo vai trò | Passed |
| TC-USER-02 | Tài khoản | N | Email đã tồn tại | Tạo tài khoản mới với email trùng | Hiện lỗi validation; không phát sinh bản ghi | Passed |
| TC-USER-03 | Tài khoản | N | Admin đang đăng nhập | Admin tự khóa, đổi vai trò hoặc xóa chính mình | Hệ thống từ chối cả trên giao diện và backend | Passed |
| TC-ROOM-01 | Phòng | P | Admin đã đăng nhập; tiện nghi đang hoạt động | Tạo phòng với mã duy nhất, giá/sức chứa hợp lệ và ảnh đúng định dạng | Phòng, tiện nghi và ảnh minh chứng được lưu; trạng thái ban đầu là trống | Passed |
| TC-ROOM-02 | Phòng | N | Đã tồn tại mã phòng | Tạo phòng có mã trùng hoặc giá/sức chứa ngoài giới hạn | Hiện lỗi đúng trường; không ghi dữ liệu một phần | Passed |
| TC-ROOM-03 | Phòng | N | Phòng có hợp đồng đang hoạt động | Sửa trạng thái mâu thuẫn hoặc xóa phòng | Hệ thống từ chối; hợp đồng và phòng được giữ nguyên | Passed |
| TC-TENANT-01 | Khách thuê | P | Admin; tài khoản client chưa liên kết | Tạo khách thuê và chọn tài khoản đó | Hồ sơ được tạo và liên kết đúng tài khoản | Passed |
| TC-TENANT-02 | Khách thuê | P | Admin đã đăng nhập | Tạo khách thuê ngoại tuyến, không có email/tài khoản đăng nhập | Tạo hồ sơ thành công | Passed |
| TC-TENANT-03 | Khách thuê | N | Khách thuê có ít nhất một hợp đồng | Gửi yêu cầu xóa khách thuê | Hệ thống từ chối; lịch sử hợp đồng được bảo toàn | Passed |
| TC-TENANT-04 | Khách thuê | N | Ngày sinh chưa đủ 18 tuổi | Khai báo người đại diện hoặc người thuê thành viên | Validation từ chối; không tạo hồ sơ thuê trong hợp đồng | Passed |
| TC-CONTRACT-01 | Hợp đồng | P | Phòng hợp lệ và khách thuê hợp lệ | Nhập dữ liệu và tạo hợp đồng | Chỉ tạo bản nháp; chưa chiếm phòng, chưa sinh hóa đơn/chỉ số | Passed |
| TC-CONTRACT-02 | Hợp đồng | N | Phòng sức chứa 4 người | Thêm người thuê thứ 5 và lưu | Báo vượt sức chứa; không lưu một phần | Passed |
| TC-CONTRACT-03 | Hợp đồng | N | Phòng đã có khoảng thuê | Tạo hợp đồng mới có thời gian chồng lấn | Hệ thống từ chối; hợp đồng không chồng lấn vẫn được phép | Passed |
| TC-CONTRACT-04 | Nhận phòng | P | Hợp đồng đã ký; tiền cọc và tháng đầu đã thanh toán; khách đã xác nhận bàn giao | Admin thực hiện nhận phòng với chỉ số điện nước hợp lệ | Hợp đồng chuyển hoạt động; phòng chuyển đang thuê; lịch sử và chỉ số được tạo đúng một lần | Passed |
| TC-CONTRACT-05 | Nhận phòng | N | Thiếu chữ ký, thanh toán hoặc xác nhận bàn giao | Admin thực hiện nhận phòng | Từ chối nhận phòng và rollback toàn bộ thay đổi | Passed |
| TC-CONTRACT-06 | Trả phòng | P | Hợp đồng đang hoạt động | Nhập thời điểm, chỉ số cuối và thực hiện trả phòng | Chuyển sang quyết toán; tạo chỉ số cuối/hóa đơn cuối khi cần; thao tác lặp không tạo trùng | Passed |
| TC-CONTRACT-07 | Hoàn tất hợp đồng | N | Hợp đồng còn công nợ hoặc chưa xử lý cọc | Thực hiện hoàn tất quyết toán | Hệ thống từ chối cho đến khi hết nợ và cọc được xử lý rõ ràng | Passed |
| TC-CONTRACT-08 | Thành viên thuê | P | Một người đại diện và hai người thuê thành viên đều đủ 18 tuổi | Tạo hợp đồng với ba người thuê | Có ba hồ sơ thuê; đúng một đại diện; thành viên không bị tự động cấp tài khoản | Passed |
| TC-UTILITY-01 | Điện nước | P | Phòng đủ điều kiện; chỉ số mới không nhỏ hơn chỉ số cũ | Chọn kỳ; nhập điện nước và lưu | Lưu đúng các phòng đã hoàn thành trong đợt ghi số | Passed |
| TC-UTILITY-02 | Điện nước | N | Có chỉ số cũ | Nhập chỉ số mới nhỏ hơn chỉ số cũ | Báo lỗi; không thay đổi dữ liệu | Passed |
| TC-UTILITY-03 | Điện nước | N | Chỉ số đã gắn với hóa đơn | Sửa chỉ số đã chốt | Hệ thống từ chối để bảo toàn dữ liệu hóa đơn | Passed |
| TC-INVOICE-01 | Hóa đơn | P | Hợp đồng và chỉ số kỳ hợp lệ | Xem trước rồi phát hành hóa đơn | Tính đúng các dòng tiền và ngày; chỉ phát hành một hóa đơn duy nhất | Passed |
| TC-INVOICE-02 | Hóa đơn | N | Thiếu chỉ số hoặc chỉ số chưa xác nhận | Phát hành hóa đơn | Từ chối phát hành; không tạo hóa đơn | Passed |
| TC-INVOICE-03 | Hóa đơn | N | Hóa đơn đã phát hành | Gửi request sửa các giá trị snapshot tài chính | Các giá trị tài chính bất biến, không bị sửa | Passed |
| TC-PAY-01 | Thanh toán | P | Khách thuê sở hữu hóa đơn còn nợ | Gửi xác nhận thanh toán kèm minh chứng | Giao dịch ở trạng thái chờ; số dư chỉ giảm sau khi admin duyệt | Passed |
| TC-PAY-02 | Thanh toán | N | Hóa đơn còn số dư xác định | Gửi số tiền âm, bằng 0, quá lớn hoặc nhiều chữ số thập phân | Báo lỗi trước khi lưu file và giao dịch | Passed |
| TC-PAY-03 | Thanh toán | N | Thanh toán đã được xử lý | Duyệt hoặc từ chối lại giao dịch | Hệ thống từ chối; số dư không thay đổi lần hai | Passed |
| TC-SUPPORT-01 | Hỗ trợ | P | Khách thuê có hợp đồng đang hoạt động | Tạo yêu cầu hỗ trợ, có hoặc không có ảnh | Yêu cầu được tạo và chỉ chủ sở hữu/admin xem được | Passed |
| TC-SUPPORT-02 | Hỗ trợ | N | Có tệp giả, sai loại hoặc vượt dung lượng | Gửi yêu cầu hỗ trợ | Báo lỗi; không lưu bản ghi hoặc tệp rác | Passed |
| TC-EXPORT-01 | Xuất dữ liệu | P | Admin đã đăng nhập; có bộ lọc | Xuất phòng/khách thuê/hóa đơn/thanh toán ra CSV | File tải về đúng bộ lọc, tiêu đề và dữ liệu tiếng Việt | Passed |
| TC-EXPORT-02 | An toàn CSV | N | Dữ liệu người dùng bắt đầu bằng `=`, `+`, `-` hoặc `@` | Xuất dữ liệu CSV và mở bằng phần mềm bảng tính | Ô dữ liệu được vô hiệu hóa công thức, không thực thi formula injection | Passed |
| TC-DASH-01 | Dashboard | P | Không có dữ liệu thống kê | Mở các trang thống kê | Hiển thị 0, không chia cho 0 hoặc lỗi máy chủ | Passed |
| TC-DASH-02 | Dashboard | P | Có thanh toán thành công/thất bại ở nhiều ngày | Xem doanh thu theo tháng | Chỉ cộng giao dịch thành công và nhóm theo ngày thanh toán thực tế | Passed |

## 5. Truy vết test case với mã nguồn kiểm thử

| Nhóm test case | File kiểm thử tự động |
| --- | --- |
| TC-AUTH, TC-ACT | `tests/Feature/AuthenticationTest.php`, `tests/Feature/AccountActivationTest.php` |
| TC-RBAC | `tests/Feature/RoleAuthorizationTest.php` |
| TC-USER | `tests/Feature/UserManagementTest.php` |
| TC-ROOM | `tests/Feature/RoomManagementTest.php` |
| TC-TENANT | `tests/Feature/TenantManagementTest.php` |
| TC-CONTRACT | `tests/Feature/ContractManagementTest.php`, `tests/Feature/ContractAccountLifecycleTest.php` |
| TC-UTILITY | `tests/Feature/UtilityReadingEntryTest.php` |
| TC-INVOICE, TC-PAY | `tests/Feature/InvoiceManagementTest.php`, `tests/Feature/ClientInvoicePortalTest.php` |
| TC-SUPPORT | `tests/Feature/SupportRequestManagementTest.php` |
| TC-EXPORT | `tests/Feature/DataExportAndPrintTest.php` |
| TC-DASH | `tests/Feature/DashboardStatisticsTest.php` |

## 6. Cách chạy và lấy bằng chứng

Chạy toàn bộ kiểm thử:

```powershell
php artisan test --compact
```

Chạy một phân hệ, ví dụ hợp đồng:

```powershell
php artisan test tests/Feature/ContractManagementTest.php
```

Chạy đúng một tình huống:

```powershell
php artisan test --filter=test_overlapping_reservations_are_rejected
```

Khi đưa vào báo cáo, nên đính kèm:

1. Bảng test case ở mục 4.
2. Ảnh chụp terminal thể hiện `243 tests, 2263 assertions` và ghi chú 2 test được PHPUnit đánh dấu `risky`.
3. Ảnh giao diện cho một số luồng quan trọng như đăng nhập, tạo phòng, tạo hợp đồng, phát hành hóa đơn và thanh toán.
4. Với test thủ công, bổ sung cột “Kết quả thực tế”, “Người kiểm thử”, “Ngày kiểm thử” và “Mã lỗi” nếu có.

## 7. Giới hạn và kiểm thử nên bổ sung

- Bộ test hiện tại chủ yếu kiểm tra backend/HTTP; chưa thay thế hoàn toàn kiểm thử trình duyệt end-to-end cho JavaScript, modal, responsive và trải nghiệm người dùng.
- Chưa có số liệu code coverage vì môi trường chưa ghi nhận driver coverage như Xdebug hoặc PCOV.
- Nên bổ sung kiểm thử hiệu năng khi nhiều người phát hành hóa đơn/thanh toán cùng lúc, kiểm thử trên MySQL giống môi trường triển khai và kiểm thử sao lưu/khôi phục dữ liệu.
- Nên thực hiện kiểm thử chấp nhận người dùng (UAT) với admin và khách thuê trên dữ liệu mẫu trước khi nghiệm thu.
