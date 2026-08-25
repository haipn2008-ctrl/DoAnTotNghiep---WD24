# Hệ thống quản lý phòng trọ Stay Master

Đây là đồ án tốt nghiệp xây dựng bằng Laravel, MySQL, Blade, Tailwind CSS và
Vite. Hệ thống hỗ trợ quản lý phòng, khách thuê, hợp đồng, chỉ số điện nước,
hóa đơn, công nợ, thanh toán, dashboard và xuất CSV.

## Trạng thái phát triển

- Giai đoạn 1 — tiếp nhận và bàn giao phòng: **đã hoàn thành**.
- Giai đoạn 2 — quản lý khách trong thời gian thuê: **đã hoàn thành**.
- Giai đoạn 3 — gia hạn, trả phòng và quyết toán: **đang hoàn thiện**.

Ranh giới nghiệp vụ và các chức năng đã chốt được ghi tại
[`docs/PROJECT_PHASE_STATUS.md`](docs/PROJECT_PHASE_STATUS.md). Không đánh dấu
giai đoạn 3 hoàn thành cho đến khi toàn bộ kịch bản trả phòng, gia hạn và quyết
toán được kiểm thử lại từ đầu đến cuối.

## Yêu cầu môi trường

- PHP 8.3 trở lên.
- Composer 2.
- MySQL hoặc MariaDB; có thể dùng MySQL đi kèm Laragon.
- Node.js 22 và npm.
- Khuyến nghị dùng Laragon trên Windows để chạy dự án nhanh nhất.

### Bộ dữ liệu cơ bản

Tài khoản có sẵn:

| Quyền | Email | Mật khẩu |
| --- | --- | --- |
| Quản trị | `admin@nhatroanphuc.test` | `Admin@123456` |
| Khách thuê đại diện | `giahuy@example.test` | `Tenant@123456` |

## Gợi ý kịch bản test thủ công

## Quy tắc người thuê trong hợp đồng

- Mỗi người tham gia thuê phòng phải đủ 18 tuổi tại ngày khai báo.
- Tất cả đều là người thuê và đều có hồ sơ trong `tenants` cùng tư cách thuê trong `contract_tenants`.
- Mỗi hợp đồng có đúng một người thuê đại diện (`representative`); các thành viên còn lại có vai trò `tenant`.
- Người đại diện là đầu mối làm việc và là tài khoản được phép quản lý hợp đồng trên cổng khách thuê.
- Người thuê thành viên không bắt buộc có tài khoản. Việc thêm ba người thuê tạo ba hồ sơ người thuê nhưng không tự động tạo ba tài khoản.
- Không xóa cứng thành viên khỏi lịch sử hợp đồng; hệ thống chuyển trạng thái để bảo toàn truy vết.

Chi tiết mô hình và các điểm kiểm soát được ghi tại `docs/CONTRACT_TENANT_RULES.md`.

### Tài khoản quản trị

1. Đăng nhập và kiểm tra số liệu dashboard.
2. Lọc phòng theo mã và trạng thái, sau đó chuyển qua nhiều trang.
3. Thêm, sửa, xem, xóa phòng và kiểm tra ảnh phòng.
4. Thêm, sửa, xem khách thuê; thử dữ liệu tiếng Việt và số điện thoại trùng.
5. Tạo hợp đồng chờ ký, hợp đồng hoạt động, gia hạn và kết thúc hợp đồng.
6. Nhập chỉ số điện nước; thử chỉ số bằng chỉ số cũ, chỉ số lớn và tải ảnh
   đồng hồ.
7. Tạo hóa đơn từ hợp đồng, xem chi tiết, sửa, in và xóa hóa đơn phù hợp.
8. Ghi nhận thanh toán đủ, một phần và nhiều lần; kiểm tra công nợ còn lại.
9. Lọc hóa đơn/thanh toán theo trạng thái, kỳ, phòng, khách thuê và từ khóa.
10. Xuất CSV phòng, khách thuê, hóa đơn và thanh toán; mở bằng Excel để kiểm
    tra tiếng Việt, số tiền và số dòng.

### Tài khoản khách thuê

1. Đăng nhập bằng tài khoản mẫu `giahuy@example.test`.
2. Kiểm tra phòng và hợp đồng đang gắn với đúng khách thuê.
3. Kiểm tra hóa đơn gần nhất, hóa đơn chưa thanh toán và số tiền còn nợ.
4. Thử tài khoản chưa có hồ sơ khách thuê để bảo đảm dashboard không lỗi.

### Dữ liệu biên nên thử thêm

- Từ khóa không có kết quả và từ khóa có dấu/không dấu.
- Trang đầu, trang giữa, trang cuối và tham số `page` lớn hơn tổng số trang.
- Tháng 1, tháng 12 và dữ liệu qua năm mới.
- Phòng trống, đang thuê, bảo trì và phòng đã đủ số người.
- Hợp đồng hết hạn hoặc đã kết thúc nhưng vẫn còn lịch sử hóa đơn.
- Hóa đơn quá hạn, chưa trả, trả một phần và đã trả đủ.
- Tải file sai định dạng hoặc ảnh lớn hơn giới hạn cho phép.

## Chạy kiểm thử tự động

Nếu PHP đã bật extension `pdo_sqlite`, chỉ cần chạy:

```bash
php artisan test
```

Nếu Laragon chưa bật `pdo_sqlite`, hãy tạo một database MySQL riêng chỉ dành
cho test, ví dụ `stay_master_test_ten_thanh_vien`, rồi chạy trong PowerShell:

```powershell
$env:APP_ENV="testing"
$env:DB_CONNECTION="mysql"
$env:DB_DATABASE="stay_master_test_ten_thanh_vien"
php artisan test
Remove-Item Env:APP_ENV, Env:DB_CONNECTION, Env:DB_DATABASE
```

> Test sử dụng `RefreshDatabase` và có thể xóa dữ liệu trong database test.
> Không trỏ lệnh test vào database phát triển đang chứa dữ liệu cần giữ.

Trước khi commit hoặc push, nên chạy tối thiểu:

```bash
php artisan test
npm run build
php artisan route:list
composer validate --no-check-publish
git diff --check
```

Có thể kiểm tra định dạng các file PHP vừa sửa bằng Pint. Ví dụ:

```powershell
vendor\bin\pint --test app\Providers\AppServiceProvider.php
```

## Quy ước làm việc nhóm

- Không commit file `.env`, thư mục `vendor`, `node_modules` hoặc dữ liệu cá
  nhân đã tải lên.
- Mỗi thành viên dùng database riêng; đặt tên có tên thành viên để tránh nhầm.
- Trước khi sửa, chạy `git status` và cập nhật nhánh đang làm việc.
- Không dùng `migrate:fresh` trên database của thành viên khác.
- Không sửa migration đã được người khác sử dụng; hãy tạo migration mới để
  thay đổi schema.
- Commit theo từng chức năng, nội dung commit ngắn gọn và dễ hiểu.
- Trước khi push, kiểm tra lại trang liên quan, test tự động và `git diff`.
- Khi xử lý xung đột, ưu tiên giữ đủ chức năng của cả hai phía và chạy lại test
  sau khi merge.

## Xử lý lỗi thường gặp

### Giao diện chưa cập nhật hoặc phân trang mất định dạng

```bash
npm install
npm run build
php artisan optimize:clear
```

Sau đó tải lại bằng `Ctrl + F5`.

### Ảnh không hiển thị

```bash
php artisan storage:link
```

Kiểm tra quyền ghi của `storage` và `bootstrap/cache`.

### Class hoặc cấu hình cũ vẫn còn trong cache

```bash
composer dump-autoload
php artisan optimize:clear
```

### Migration hoặc dữ liệu test bị rối

Chỉ trên database cá nhân có thể xóa:

```bash
php artisan migrate:fresh --seed
```

## Các khu vực chính trong mã nguồn

| Thư mục | Nội dung |
| --- | --- |
| `app/Http/Controllers/Admin` | Xử lý các chức năng quản trị |
| `app/Models` | Model và quan hệ dữ liệu |
| `app/Services` | Nghiệp vụ dùng chung, gồm tạo hóa đơn |
| `database/migrations` | Lịch sử cấu trúc database |
| `database/seeders` | Dữ liệu mẫu cơ bản |
| `resources/views` | Giao diện Blade/Tailwind |
| `routes/web.php` | Route quản trị, khách thuê và đăng nhập |
| `tests/Feature` | Test luồng chức năng chính |

Khi phát hiện lỗi, hãy ghi rõ tài khoản đang dùng, URL, bước tái hiện, dữ liệu
đầu vào, kết quả mong đợi, kết quả thực tế và ảnh chụp màn hình. Thông tin này
giúp thành viên khác tái hiện và sửa lỗi nhanh hơn.
