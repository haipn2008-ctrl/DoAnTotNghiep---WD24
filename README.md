# Hệ thống quản lý phòng trọ Stay Master

Đây là đồ án tốt nghiệp xây dựng bằng Laravel, MySQL, Blade, Tailwind CSS và
Vite. Hệ thống hỗ trợ quản lý phòng, khách thuê, hợp đồng, chỉ số điện nước,
hóa đơn, công nợ, thanh toán, dashboard và xuất CSV.

README này dành cho tất cả thành viên trong nhóm. Mỗi người nên dùng database
riêng trên máy của mình để có thể thêm, sửa, xóa và chạy lại dữ liệu test mà
không ảnh hưởng đến người khác.

## Yêu cầu môi trường

- PHP 8.3 trở lên.
- Composer 2.
- MySQL hoặc MariaDB; có thể dùng MySQL đi kèm Laragon.
- Node.js 22 và npm.
- Khuyến nghị dùng Laragon trên Windows để chạy dự án nhanh nhất.

## Cài đặt lần đầu

Mở Terminal của Laragon tại thư mục dự án và chạy:

```bash
composer install
npm install
```

Tạo file môi trường và khóa ứng dụng:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Tạo một database MySQL riêng, ví dụ `stay_master_ten_thanh_vien`, sau đó sửa
phần kết nối trong `.env`:

```dotenv
APP_NAME="Stay Master"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stay_master_ten_thanh_vien
DB_USERNAME=root
DB_PASSWORD=
```

Tạo liên kết để hiển thị ảnh phòng và ảnh đồng hồ điện nước:

```bash
php artisan storage:link
```

Sau đó chọn một trong hai bộ dữ liệu ở phần tiếp theo.

## Chọn dữ liệu để test

### Bộ dữ liệu cơ bản

Phù hợp khi cần kiểm tra nhanh thao tác thêm, sửa và xóa:

```bash
php artisan migrate:fresh --seed
```

Tài khoản có sẵn:

| Quyền | Email | Mật khẩu |
| --- | --- | --- |
| Quản trị | `admin@gmail.com` | `123456` |
| Khách thuê | `user@gmail.com` | `123456` |

### Bộ dữ liệu lớn

Khuyến nghị dùng bộ này để test bộ lọc, phân trang, dashboard, công nợ, xuất
CSV và hiệu năng:

```bash
php artisan migrate:fresh --seed --seeder='Database\Seeders\LargeTestDataSeeder' --force
```

Tài khoản mẫu đều dùng mật khẩu `password`:

| Quyền | Email | Ghi chú |
| --- | --- | --- |
| Quản trị | `admin01@test.local` | Có thể dùng `admin01` đến `admin08` |
| Khách thuê | `tenant001@test.local` | Có thể dùng `tenant001` đến `tenant070` |

Bộ dữ liệu lớn tạo cố định:

- 8 tài khoản quản trị và 70 tài khoản khách thuê.
- 90 phòng: 60 đang thuê, 20 phòng trống và 10 phòng bảo trì.
- 130 hợp đồng: 60 đang hoạt động, 20 chờ ký, 25 hết hạn và 25 đã kết thúc.
- 1.080 bản ghi điện nước trong 12 tháng.
- 1.080 hóa đơn và 5.400 chi tiết hóa đơn.
- 965 giao dịch gồm thành công, chờ xử lý và thất bại.
- Hóa đơn đã trả đủ, trả một phần, chưa trả và quá hạn.
- Dữ liệu biên như mức tiêu thụ bằng 0, mức tiêu thụ rất cao, nội dung dài và
  tiếng Việt có dấu.

> **Cảnh báo:** `migrate:fresh` xóa toàn bộ bảng và dữ liệu của database đang
> được cấu hình trong `.env`. Chỉ chạy trên database cá nhân dành cho phát
> triển hoặc kiểm thử. Tuyệt đối không chạy trên database dùng chung hoặc
> production.

## Chạy dự án

Nếu sử dụng máy chủ tích hợp của Laravel, mở hai terminal:

```bash
php artisan serve
```

```bash
npm run dev
```

Sau đó truy cập `http://127.0.0.1:8000`. Nếu dùng Auto Virtual Hosts của
Laragon, có thể mở tên miền `.test` do Laragon tạo và vẫn giữ `npm run dev`
để cập nhật giao diện trong lúc phát triển.

Để kiểm tra bản build production:

```bash
npm run build
```

## Gợi ý kịch bản test thủ công

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

1. Đăng nhập bằng một tài khoản `tenant...@test.local`.
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

Hoặc tạo lại bộ dữ liệu lớn bằng lệnh ở phần “Bộ dữ liệu lớn”.

## Các khu vực chính trong mã nguồn

| Thư mục | Nội dung |
| --- | --- |
| `app/Http/Controllers/Admin` | Xử lý các chức năng quản trị |
| `app/Models` | Model và quan hệ dữ liệu |
| `app/Services` | Nghiệp vụ dùng chung, gồm tạo hóa đơn |
| `database/migrations` | Lịch sử cấu trúc database |
| `database/seeders` | Dữ liệu mẫu cơ bản và dữ liệu lớn |
| `resources/views` | Giao diện Blade/Tailwind |
| `routes/web.php` | Route quản trị, khách thuê và đăng nhập |
| `tests/Feature` | Test luồng chức năng chính |

Khi phát hiện lỗi, hãy ghi rõ tài khoản đang dùng, URL, bước tái hiện, dữ liệu
đầu vào, kết quả mong đợi, kết quả thực tế và ảnh chụp màn hình. Thông tin này
giúp thành viên khác tái hiện và sửa lỗi nhanh hơn.
