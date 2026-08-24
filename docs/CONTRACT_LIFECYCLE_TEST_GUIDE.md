# Hướng dẫn kiểm thử vòng đời hợp đồng thuê phòng

Tài liệu này dành cho người kiểm thử nghiệp vụ. Mục tiêu là kiểm tra đúng thứ tự: tạo bản nháp → chờ ký → chờ cọc → chờ nhận phòng → đang ở → quyết toán → hoàn tất. Không dùng các câu lệnh trong tài liệu để sửa dữ liệu.

## 1. Chuẩn bị

1. Sao lưu database nếu dùng database chung. Nên dùng một database kiểm thử riêng.
2. Nếu database kiểm thử được phép xóa hoàn toàn, tại thư mục dự án chạy `php artisan migrate:fresh --seed`. Nếu cần giữ dữ liệu đang có, chạy `php artisan migrate` rồi `php artisan db:seed`.
3. Chạy ứng dụng bằng `php artisan serve` và `npm run dev`, hoặc mở qua Laragon.
4. Sau khi seed, dùng các tài khoản kiểm thử sau tại trang `/login`:

| Mục đích | Email | Mật khẩu | Trạng thái ban đầu |
|---|---|---|---|
| Admin 1 | `admin@nhatroanphuc.test` | `Admin@123456` | Admin đang hoạt động |
| Admin 2 | `quanly@nhatroanphuc.test` | `Admin@123456` | Admin đang hoạt động |
| Client cho KH-A | `qa.client.a@example.test` | `Test@123456` | Client active, chưa gắn khách thuê/hợp đồng |
| Client cho KH-B | `qa.client.b@example.test` | `Test@123456` | Client active, chưa gắn khách thuê/hợp đồng |
| Client cho KH-C | `qa.client.c@example.test` | `Test@123456` | Client active, chưa gắn khách thuê/hợp đồng |

   Chỉ dùng các tài khoản này trong môi trường kiểm thử. Không đổi mật khẩu dùng chung thành mật khẩu của tài khoản thật.
5. Vào **Hệ thống → Nhà trọ & thanh toán** và nhập đầy đủ tên/địa chỉ tài sản, thông tin chủ nhà và tài khoản nhận tiền. Dữ liệu tài sản/chủ nhà được chụp snapshot khi tạo hợp đồng. Vào **Hệ thống → Phí dịch vụ** để nhập cùng lúc đơn giá điện, nước, Internet, dịch vụ chung và gửi xe.
6. Tạo các phòng sau:

| Phòng | Trạng thái | Giá | Sức chứa | Mục đích |
|---|---|---:|---:|---|
| TEST-A | available | 3.000.000 | 3 | Luồng thông thường |
| TEST-B | available | 3.500.000 | 2 | Trùng lịch/concurrency |
| TEST-M | maintenance | 3.000.000 | 2 | Kiểm tra bảo trì |

### Kiểm tra lúc tạo phòng

1. Vào **Phòng → Thêm phòng mới**. Màn hình không được có ô nhập **Số người hiện tại** và không cho chọn **Trạng thái**.
2. Tạo TEST-A. Chọn các tài sản như Giường, Bàn, Tủ lạnh; nhập số lượng và chọn **Sử dụng bình thường** hoặc **Có hư hỏng**.
3. Chọn nhiều ảnh hiện trạng cùng lúc (toàn cảnh, từng tài sản và các vết xước có sẵn), rồi bấm **Thêm phòng**.
4. Kết quả mong đợi: TEST-A luôn hiển thị **Trống**, số người `0`; không thể sửa hai giá trị này bằng request giả. Trong database, `rooms.status = 'available'`, `rooms.current_people = 0`; số lượng nằm tại `amenity_room.quantity`.
5. Mở **Chi tiết phòng → Nhật ký ảnh hiện trạng**. Mỗi ảnh phải có loại, thời điểm, người tải lên và mã SHA-256; tải thêm ảnh không được làm mất ảnh cũ.
6. Nhật ký chỉ có hai loại: **Trước khi bàn giao phòng** và **Sau khi nhận lại phòng**. Loại sau bắt buộc gắn đúng hợp đồng. Thời điểm ghi nhận do máy chủ tự lưu lúc tải ảnh và không có ô cho Admin sửa. Hệ thống phải từ chối loại ảnh khác hoặc hợp đồng thuộc phòng khác.
7. Khi tạo/sửa bản nháp hợp đồng, chọn **Người đại diện thuê** và từng **Thành viên cùng ở**. Trang chi tiết phòng phải hiện đúng tổng số người, đại diện và liên kết hồ sơ của từng thành viên. Nếu hợp đồng cũ chỉ có số lượng mà thiếu danh tính, giao diện phải cảnh báo phần còn thiếu thay vì tự đoán.

Để xác minh chỉ đọc bằng Tinker:

```php
$r = App\Models\Room::where('room_code', 'TEST-A')->with(['amenities', 'images'])->first();
$r?->only(['status', 'current_people', 'max_people']);
$r?->amenities->map(fn ($a) => [$a->name, $a->pivot->quantity, $a->pivot->condition, $a->pivot->note]);
$r?->images->map->only(['evidence_type', 'contract_id', 'taken_at', 'uploaded_by', 'sha256']);
```

7. Tạo khách `KH-A`, `KH-B`, `KH-C` và lần lượt liên kết với ba tài khoản `qa.client.a`, `qa.client.b`, `qa.client.c` ở bảng trên. Dùng CCCD/số điện thoại khác nhau cho từng khách.

## 2. Cách đọc kết quả

Trong **Quản lý hợp đồng → Chi tiết**, kiểm tra thẻ trạng thái, khối “Bước tiếp theo”, các cảnh báo, số tiền cọc và bảng lịch sử. Phòng chỉ chuyển sang `occupied` ở lúc check-in; trước đó vẫn là `available`.

Có thể xác minh chỉ đọc bằng Tinker:

```bash
php artisan tinker
```

```php
$c = App\Models\Contract::with(['room','invoices.payments','utilityReadings','statusHistories','lifecycleAlerts'])->where('contract_code', 'MÃ_HỢP_ĐỒNG')->first();
$c?->only(['id','status','signed_at','actual_move_in_at','actual_move_out_at','cancelled_at','completed_at','deposit_status','deposit_resolution']);
$c?->room?->only(['status','current_people']);
$c?->statusHistories->map->only(['from_status','to_status','action','reason','performed_by','performed_at']);
```

Hoặc SQL chỉ đọc:

```sql
SELECT id, contract_code, status, signed_at, actual_move_in_at, actual_move_out_at,
       cancelled_at, completed_at, deposit_status, deposit_resolution
FROM contracts WHERE contract_code = 'MÃ_HỢP_ĐỒNG';
SELECT from_status, to_status, action, reason, performed_by, performed_at
FROM contract_status_histories WHERE contract_id = ID_HỢP_ĐỒNG ORDER BY id;
```

Để làm lại một kịch bản, không xóa hợp đồng cũ. Hãy hủy hợp đồng nếu nghiệp vụ cho phép, đặt mã khách/phòng mới, hoặc dùng database kiểm thử mới. Điều này đồng thời kiểm tra yêu cầu giữ lịch sử.

## 3. Ma trận chuyển trạng thái

| Nguồn | Hành động | Đích | Người thực hiện | Điều kiện chính | Phải từ chối khi |
|---|---|---|---|---|---|
| draft | Gửi chờ ký | pending_signature | Admin active | Lịch nhận/hạn giữ hợp lệ, phòng không bảo trì | Client gọi; thiếu lịch; phòng bảo trì |
| pending_signature | Trả lại bản nháp | draft | Admin active | Chưa ký, chưa có chứng từ; có lý do | Đã có signed_at/invoice/payment |
| pending_signature | Xác nhận đã ký | pending_deposit | Admin active | signed_at không tương lai, cọc > 0, không trùng lịch | Gọi từ draft; ngày tương lai; trùng phòng |
| pending_signature | Xác nhận đã ký | awaiting_move_in | Admin active | signed_at hợp lệ, cọc = 0, không trùng lịch | Thiếu lịch/hạn giữ; phòng bảo trì |
| pending_deposit | Thanh toán đủ cọc thành công | awaiting_move_in | Admin/payment processor | Một hóa đơn cọc; tổng payment success đủ cọc | pending/failed/thiếu tiền |
| awaiting_move_in | Check-in | active | Admin active | Đủ cọc, phòng trống, đủ sức chứa, biên bản và chỉ số hợp lệ | Thiếu chữ ký/cọc; phòng occupied/maintenance; chỉ số lùi |
| awaiting_move_in | Gia hạn giữ chỗ | awaiting_move_in | Admin active | Hạn mới tương lai, sau hạn cũ; lý do bắt buộc | Hạn không tăng; thiếu lý do |
| draft/pending_signature/pending_deposit/awaiting_move_in | Hủy | cancelled | Admin active | Lý do bắt buộc; cọc đã thu được đưa vào xử lý | active/expired; thiếu lý do |
| active | Scheduler hết hạn | expired | Hệ thống | end_date đã qua, chưa checkout | Chưa qua hạn; đã checkout |
| active/expired | Gia hạn hợp đồng | active hoặc expired | Admin active | Ngày mới sau ngày cũ; không xung đột; có lý do | Trùng reservation tương lai |
| active/expired | Checkout | settling | Admin active | Ngày/chỉ số cuối/lý do hợp lệ | Trạng thái khác; chỉ số lùi; ngày tương lai |
| settling | Hoàn tất quyết toán | completed | Admin active | Đã checkout, hết nợ hoặc write-off có lý do, cọc đã xử lý | Còn nợ; cọc chưa xử lý; chưa xác nhận |
| completed/cancelled | Bất kỳ transition ngược | Không đổi | Không ai | Không có | Luôn phải từ chối hoặc idempotent đúng action đã hoàn tất |

## 4. Các kịch bản thực tế

### 4.1 Hợp đồng không cọc

- **Mục tiêu:** đi từ bản nháp đến chờ nhận phòng mà không phát hành hóa đơn cọc.
- **Điều kiện ban đầu:** TEST-A available, KH-A chưa có reservation giao nhau.
- **Các bước:** tạo hợp đồng; nhập cọc `0`; lưu; bấm **Gửi chờ ký**; bấm **Xác nhận đã ký**.
- **Dữ liệu nhập:** thời hạn từ ngày mai đến sau 12 tháng; lịch nhận = ngày bắt đầu; hạn giữ = lịch nhận + 1 ngày; ngày ký = hôm nay.
- **Giao diện mong đợi:** lần lượt `draft`, `pending_signature`, `awaiting_move_in`; không có nút phát hành hóa đơn cọc.
- **Database mong đợi:** `signed_at` có giá trị, `deposit_resolution=not_required`, không có invoice loại `deposit`; phòng vẫn available/0 người.
- **Không được xảy ra:** tự active, tự tạo handover hoặc chiếm phòng.
- **Khôi phục:** hủy với lý do “Kết thúc test không cọc”, hoặc tiếp tục dùng cho ca check-in.

### 4.2 Hợp đồng có cọc

- **Mục tiêu:** xác nhận ký đưa hợp đồng vào chờ cọc.
- **Điều kiện ban đầu:** TEST-B available, KH-B không có lịch trùng.
- **Các bước:** tạo bản nháp; gửi chờ ký; xác nhận đã ký.
- **Dữ liệu nhập:** cọc `2.000.000`; các ngày hợp lệ như 4.1.
- **Giao diện mong đợi:** trạng thái `pending_deposit`, có nút **Phát hành hóa đơn cọc**.
- **Database mong đợi:** `signed_confirmed_by` là Admin; chưa có invoice cho đến khi bấm phát hành.
- **Không được xảy ra:** tự thu cọc hoặc chuyển phòng occupied.
- **Khôi phục:** giữ lại cho các ca 4.3–4.5.

### 4.3 Cọc một phần

- **Mục tiêu:** payment success chưa đủ không mở check-in.
- **Điều kiện ban đầu:** hợp đồng 4.2 đã có đúng một hóa đơn cọc 2.000.000.
- **Các bước:** mở hóa đơn; ghi nhận thanh toán tiền mặt `700.000`.
- **Dữ liệu nhập:** ngày thanh toán hôm nay, phương thức tiền mặt.
- **Giao diện mong đợi:** đã thu 700.000, còn thiếu 1.300.000; trạng thái vẫn `pending_deposit`.
- **Database mong đợi:** một payment `success`; invoice `partial`; contract `deposit_status=pending`.
- **Không được xảy ra:** xuất hiện nút check-in hoặc `awaiting_move_in`.
- **Khôi phục:** tiếp tục ca 4.4.

### 4.4 Cọc đủ

- **Mục tiêu:** tổng payment success đủ cọc chuyển đúng một lần sang chờ nhận phòng.
- **Điều kiện ban đầu:** còn thiếu 1.300.000 từ ca 4.3.
- **Các bước:** ghi nhận thêm `1.300.000`; tải lại chi tiết hợp đồng.
- **Dữ liệu nhập:** payment success, ngày hôm nay.
- **Giao diện mong đợi:** cọc đủ, trạng thái `awaiting_move_in`, có form check-in.
- **Database mong đợi:** tổng success = 2.000.000; invoice paid; đúng một history `deposit_completed`.
- **Không được xảy ra:** thu vượt cọc hoặc hai history khi tải/gửi lại.
- **Khôi phục:** giữ cho ca nhận phòng.

### 4.5 Thanh toán cọc thất bại

- **Mục tiêu:** payment failed không được tính là cọc.
- **Điều kiện ban đầu:** một hợp đồng mới `pending_deposit`, hóa đơn cọc 1.000.000.
- **Các bước:** gửi một thanh toán chuyển khoản kèm biên lai rồi để Admin từ chối.
- **Dữ liệu nhập:** 1.000.000, ghi chú “Sai nội dung chuyển khoản”.
- **Giao diện mong đợi:** payment thất bại; hợp đồng vẫn `pending_deposit`; còn thiếu 1.000.000.
- **Database mong đợi:** payment `failed`, không có `deposit_completed`.
- **Không được xảy ra:** invoice paid hoặc hợp đồng awaiting_move_in.
- **Khôi phục:** hủy hợp đồng với lý do test.

### 4.6 Tạo trước nhiều tháng nhưng chưa nhận phòng

- **Mục tiêu:** reservation tương lai không làm phòng occupied.
- **Điều kiện ban đầu:** phòng available, không có lịch giao nhau.
- **Các bước:** tạo, ký và hoàn tất cọc cho hợp đồng bắt đầu sau 3 tháng.
- **Dữ liệu nhập:** scheduled_move_in_date = start_date sau 3 tháng; reservation_expires_at = start_date + 1 ngày.
- **Giao diện mong đợi:** `awaiting_move_in`, hiển thị ngày dự kiến/hạn giữ.
- **Database mong đợi:** room available/current_people=0; không có handover.
- **Không được xảy ra:** khóa vật lý phòng trong toàn bộ 3 tháng bằng room.status.
- **Khôi phục:** hủy hoặc giữ cho test lịch tương lai.

### 4.7 Nhận phòng đúng ngày

- **Mục tiêu:** check-in chuẩn.
- **Điều kiện ban đầu:** `awaiting_move_in`, hôm nay đúng scheduled date, đủ cọc.
- **Các bước:** nhập thời điểm hiện tại, điện `100`, nước `10`; tích xác nhận biên bản; bấm **Xác nhận nhận phòng**.
- **Dữ liệu nhập:** không cần lý do lệch lịch.
- **Giao diện mong đợi:** `active`, hiện ngày nhận thực tế và người xác nhận.
- **Database mong đợi:** room occupied/current_people đúng hợp đồng; đúng một handover và một history check_in.
- **Không được xảy ra:** dữ liệu chỉ cập nhật một phần.
- **Khôi phục:** checkout hợp lệ khi kết thúc test.

### 4.8 Nhận phòng sớm

- **Mục tiêu:** nhận trước lịch chỉ được phép có lý do.
- **Điều kiện ban đầu:** awaiting_move_in, lịch nhận ngày mai.
- **Các bước:** thử check-in hôm nay không lý do; sau đó nhập lý do và gửi lại.
- **Dữ liệu nhập:** “Khách đề nghị nhận sớm, phòng đã sẵn sàng”.
- **Giao diện mong đợi:** lần đầu báo lỗi; lần sau active.
- **Database mong đợi:** history check_in lưu lý do lệch lịch.
- **Không được xảy ra:** request đầu tạo reading hoặc chiếm phòng.
- **Khôi phục:** checkout.

### 4.9 Nhận phòng muộn

- **Mục tiêu:** nhận sau lịch cũng bắt buộc lý do.
- **Điều kiện ban đầu:** awaiting_move_in, scheduled date hôm qua, hạn giữ chưa hết hoặc đã được gia hạn.
- **Các bước:** thử không lý do rồi gửi lại với lý do.
- **Dữ liệu nhập:** “Khách báo chuyến đi bị trễ”.
- **Giao diện mong đợi:** lần đầu lỗi; lần sau active.
- **Database mong đợi:** actual_move_in_at hôm nay; history có reason.
- **Không được xảy ra:** tự sửa scheduled date.
- **Khôi phục:** checkout.

### 4.10 Quá hạn nhận phòng rồi gia hạn

- **Mục tiêu:** quá hạn tạo cảnh báo, Admin chủ động gia hạn.
- **Điều kiện ban đầu:** awaiting_move_in, reservation_expires_at đã qua.
- **Các bước:** chạy `php artisan contracts:process-lifecycle`; mở chi tiết; bấm **Gia hạn giữ phòng**.
- **Dữ liệu nhập:** hạn mới sau hạn cũ và trong tương lai; lý do “Khách xin lùi 3 ngày”.
- **Giao diện mong đợi:** cảnh báo quá hạn trước thao tác; sau đó cảnh báo được giải quyết, trạng thái không đổi.
- **Database mong đợi:** một alert `move_in_overdue` có `resolved_at`; history `extend_move_in_deadline`.
- **Không được xảy ra:** xóa hợp đồng, hoàn/mất cọc tự động.
- **Khôi phục:** hủy hoặc check-in.

### 4.11 Quá hạn nhận phòng rồi hủy

- **Mục tiêu:** xử lý reservation quá hạn bằng hủy có lý do.
- **Điều kiện ban đầu:** như 4.10, chưa thu cọc.
- **Các bước:** bấm **Hủy hợp đồng**, nhập lý do.
- **Dữ liệu nhập:** “Không liên lạc được với khách sau hạn giữ”.
- **Giao diện mong đợi:** cancelled; không còn action nhận phòng.
- **Database mong đợi:** cancelled_at/by/reason đầy đủ; alert cũ resolved.
- **Không được xảy ra:** hard delete.
- **Khôi phục:** tạo hợp đồng mới, không phục hồi bản đã hủy.

### 4.12 Hủy khi chưa thu cọc

- **Mục tiêu:** hủy draft/chờ ký/chờ cọc không có tiền.
- **Điều kiện ban đầu:** một hợp đồng thuộc một trong các trạng thái cho phép, tổng payment success = 0.
- **Các bước:** hủy và nhập lý do.
- **Dữ liệu nhập:** “Hai bên không tiếp tục”.
- **Giao diện mong đợi:** cancelled.
- **Database mong đợi:** deposit_resolution=not_required; giữ nguyên contract/history.
- **Không được xảy ra:** xóa invoice/payment có sẵn.
- **Khôi phục:** tạo dữ liệu mới.

### 4.13 Hủy khi đã thu cọc

- **Mục tiêu:** không âm thầm coi cọc đã hoàn.
- **Điều kiện ban đầu:** pending_deposit hoặc awaiting_move_in có payment success > 0.
- **Các bước:** hủy với lý do.
- **Dữ liệu nhập:** “Khách hủy, chờ biên bản xử lý cọc”.
- **Giao diện mong đợi:** cancelled và cảnh báo cọc cần xử lý.
- **Database mong đợi:** deposit_resolution=`pending_resolution`; alert `cancelled_deposit_resolution`; payment giữ nguyên.
- **Không được xảy ra:** tự đổi payment, tự đặt refunded/retained.
- **Khôi phục:** đối soát bằng quy trình/chứng từ thực tế; dùng hợp đồng mới cho test sau.

### 4.14 Double-click nút hành động

- **Mục tiêu:** request lặp không sinh dữ liệu đôi.
- **Điều kiện ban đầu:** chuẩn bị riêng cho ký, phát hành cọc, check-in và checkout.
- **Các bước:** double-click nhanh từng nút hoặc gửi cùng request hai lần.
- **Dữ liệu nhập:** cùng payload ở cả hai lần.
- **Giao diện mong đợi:** một kết quả thành công; nút bị disable sau lần bấm đầu.
- **Database mong đợi:** mỗi transition đúng một history; một invoice cọc; một handover; một checkout reading.
- **Không được xảy ra:** lỗi 500 hoặc bản ghi trùng.
- **Khôi phục:** tiếp tục vòng đời bình thường.

### 4.15 Hai Admin thao tác cùng lúc

- **Mục tiêu:** khóa transaction ngăn hai người cùng check-in/phát hành cọc.
- **Điều kiện ban đầu:** hai trình duyệt đăng nhập hai Admin, cùng mở một hợp đồng.
- **Các bước:** đếm 3-2-1 và cùng bấm action; tải lại cả hai cửa sổ.
- **Dữ liệu nhập:** payload giống nhau.
- **Giao diện mong đợi:** chỉ một thao tác tạo thay đổi; cửa sổ còn lại nhận trạng thái mới/idempotent hoặc lỗi nghiệp vụ rõ ràng.
- **Database mong đợi:** các khóa lifecycle_event_key duy nhất; không trùng history/reading/invoice.
- **Không được xảy ra:** hai khách cùng chiếm một phòng hoặc tổng cọc vượt yêu cầu.
- **Khôi phục:** tiếp tục từ trạng thái cuối.

### 4.16 Phòng đang bảo trì

- **Mục tiêu:** không cho giữ/check-in phòng maintenance.
- **Điều kiện ban đầu:** TEST-M maintenance.
- **Các bước:** thử tạo/gửi ký/check-in bằng phòng này tùy dữ liệu đang có.
- **Dữ liệu nhập:** ngày và chỉ số hợp lệ.
- **Giao diện mong đợi:** lỗi tiếng Việt nêu phòng bảo trì.
- **Database mong đợi:** không transition, không reading, current_people không đổi.
- **Không được xảy ra:** active hoặc occupied.
- **Khôi phục:** đổi phòng về available sau khi kết thúc bảo trì.

### 4.17 Phòng có khách cũ chưa trả

- **Mục tiêu:** chặn khách mới dù reservation tương lai đã sẵn sàng.
- **Điều kiện ban đầu:** hợp đồng cũ active/expired và room occupied; hợp đồng mới awaiting_move_in.
- **Các bước:** thử check-in hợp đồng mới.
- **Dữ liệu nhập:** lý do lệch lịch nếu cần, chỉ số hợp lệ.
- **Giao diện mong đợi:** báo phòng còn khách cũ.
- **Database mong đợi:** hợp đồng cũ và phòng giữ nguyên; hợp đồng mới vẫn awaiting_move_in.
- **Không được xảy ra:** current_people bị ghi đè.
- **Khôi phục:** checkout khách cũ rồi thử lại khi không xung đột.

### 4.18 Hai hợp đồng trùng thời gian

- **Mục tiêu:** chặn hai reservation giao nhau.
- **Điều kiện ban đầu:** hợp đồng A của TEST-B đã ký, từ 01/10 đến 31/12.
- **Các bước:** tạo B từ 01/12 đến 01/02; gửi chờ ký; xác nhận ký.
- **Dữ liệu nhập:** cùng phòng, khách khác.
- **Giao diện mong đợi:** xác nhận ký B bị từ chối và chỉ rõ mã A.
- **Database mong đợi:** B vẫn pending_signature, signed_at null.
- **Không được xảy ra:** B giữ lịch hoặc có hóa đơn cọc.
- **Khôi phục:** hủy B hoặc sửa khi trả lại draft.

### 4.19 Hợp đồng tương lai không trùng

- **Mục tiêu:** cho phép nhiều hợp đồng nối tiếp cùng phòng.
- **Điều kiện ban đầu:** A kết thúc 31/12.
- **Các bước:** tạo và ký B bắt đầu 01/01 năm sau.
- **Dữ liệu nhập:** khoảng ngày không giao nhau.
- **Giao diện mong đợi:** ký thành công, B chờ cọc/chờ nhận.
- **Database mong đợi:** cả A và B là reservation hợp lệ; room.status chỉ phản ánh người đang ở.
- **Không được xảy ra:** từ chối chỉ vì room.status occupied nếu lịch B ở tương lai không giao nhau.
- **Khôi phục:** hủy B nếu không dùng tiếp.

### 4.20 Hết hạn nhưng khách vẫn ở

- **Mục tiêu:** expired không giải phóng phòng/tài khoản.
- **Điều kiện ban đầu:** active, end_date hôm qua, chưa checkout.
- **Các bước:** chạy lifecycle command; mở dashboard và chi tiết.
- **Dữ liệu nhập:** không có.
- **Giao diện mong đợi:** expired/cảnh báo gia hạn hoặc trả phòng.
- **Database mong đợi:** contract expired; room occupied/current_people giữ nguyên; Client vẫn active.
- **Không được xảy ra:** phòng available, khách bị khóa, hợp đồng completed.
- **Khôi phục:** gia hạn hoặc checkout.

### 4.21 Gia hạn hợp đồng

- **Mục tiêu:** active/expired gia hạn có kiểm tra lịch.
- **Điều kiện ban đầu:** active hoặc expired; không có reservation giao nhau ở phần gia hạn.
- **Các bước:** bấm **Gia hạn hợp đồng**; nhập ngày kết thúc mới và lý do.
- **Dữ liệu nhập:** ngày mới sau ngày cũ; “Hai bên ký phụ lục PL-01”.
- **Giao diện mong đợi:** active nếu ngày mới chưa qua.
- **Database mong đợi:** end_date mới; history extend_contract chứa ngày cũ/mới và Admin.
- **Không được xảy ra:** ghi đè lịch nếu giao với hợp đồng tương lai.
- **Khôi phục:** không sửa ngược; checkout khi cần.

### 4.22 Checkout còn nợ

- **Mục tiêu:** checkout chỉ chuyển settling.
- **Điều kiện ban đầu:** active/expired có invoice unpaid/partial.
- **Các bước:** nhập chỉ số cuối, ngày trả và lý do; checkout.
- **Dữ liệu nhập:** chỉ số >= gần nhất, “Khách trả phòng, còn công nợ”.
- **Giao diện mong đợi:** settling; phòng available; còn hiện công nợ; hoàn tất bị chặn.
- **Database mong đợi:** actual_move_out_at/checked_out_by; checkout reading; invoice giữ unpaid/partial.
- **Không được xảy ra:** completed hoặc xóa nợ tự động.
- **Khôi phục:** thanh toán hoặc write-off có thẩm quyền/lý do.

### 4.23 Checkout có hư hỏng/cần quyết toán

- **Mục tiêu:** tạo đúng một hóa đơn quyết toán cuối.
- **Điều kiện ban đầu:** active, có biên bản hư hỏng.
- **Các bước:** checkout; nhập settlement_amount và mô tả.
- **Dữ liệu nhập:** `500.000`; “Hỏng khóa cửa theo biên bản BB-01”.
- **Giao diện mong đợi:** settling và xuất hiện hóa đơn settlement.
- **Database mong đợi:** một invoice lifecycle settlement; mô tả/chi tiết đúng.
- **Không được xảy ra:** tạo hai invoice khi gửi lại.
- **Khôi phục:** thu tiền/khấu trừ cọc theo chứng từ.

### 4.24 Hoàn tất sau khi hết nợ và xử lý cọc

- **Mục tiêu:** settling → completed đúng điều kiện.
- **Điều kiện ban đầu:** đã checkout; mọi invoice paid/written_off; cọc có quyết định.
- **Các bước:** bấm **Hoàn tất quyết toán**; chọn hoàn/khấu trừ/giữ cọc; nhập ghi chú nếu khấu trừ/giữ; xác nhận.
- **Dữ liệu nhập:** ví dụ `deducted`, “Khấu trừ 500.000 theo BB-01”.
- **Giao diện mong đợi:** completed, không còn action quản trị vòng đời.
- **Database mong đợi:** completed_at/by; deposit_resolved_at/by; history complete_settlement.
- **Không được xảy ra:** hoàn tất nếu còn invoice mở hoặc thiếu quyết định cọc.
- **Khôi phục:** không phục hồi; tạo hợp đồng mới.

### 4.25 Client cố truy cập endpoint Admin

- **Mục tiêu:** backend phân quyền độc lập với việc ẩn nút.
- **Điều kiện ban đầu:** đăng nhập Client; biết ID hợp đồng.
- **Các bước:** dùng trình duyệt/Postman gửi POST tới `/admin/contracts/{id}/check-in` hoặc `/mark-signed`.
- **Dữ liệu nhập:** payload hợp lệ.
- **Giao diện mong đợi:** HTTP 403/không có quyền.
- **Database mong đợi:** không thay đổi contract/room/history.
- **Không được xảy ra:** redirect thành công hoặc tạo reading.
- **Khôi phục:** không cần.

### 4.26 Request giả bỏ qua trạng thái

- **Mục tiêu:** không thể draft → active hay active → completed.
- **Điều kiện ban đầu:** một draft và một active.
- **Các bước:** gửi thẳng endpoint check-in cho draft; gửi complete-settlement cho active; thử thêm `status=active` vào form update.
- **Dữ liệu nhập:** payload đủ trường.
- **Giao diện mong đợi:** lỗi validation/409; trạng thái không đổi.
- **Database mong đợi:** status/audit field không đổi do mass assignment; không history giả.
- **Không được xảy ra:** bỏ qua transition.
- **Khôi phục:** không cần.

### 4.27 Dữ liệu cũ sau migration

- **Mục tiêu:** kiểm tra backfill an toàn.
- **Điều kiện ban đầu:** bản sao database cũ có pending, active, terminated và dữ liệu tài chính/chỉ số.
- **Các bước:** sao lưu; chạy `php artisan migrate`; chạy `php artisan contracts:audit-lifecycle`.
- **Dữ liệu nhập:** không có.
- **Giao diện mong đợi:** active cũ vẫn đang ở; pending cũ thành draft/chưa ký; terminated còn nợ thành settling.
- **Database mong đợi:** active có signed_at/actual_move_in_at backfill; history `legacy_migration` metadata migrated; invoice/payment/reading còn nguyên.
- **Không được xảy ra:** pending bị coi đã ký; mất chứng từ; tự giải phóng phòng active.
- **Khôi phục:** nếu audit có vấn đề, giữ báo cáo và phục hồi bản sao; không sửa hàng loạt khi chưa đối soát.

### 4.28 Scheduler chạy lặp hai lần

- **Mục tiêu:** scheduler idempotent.
- **Điều kiện ban đầu:** có hợp đồng quá hạn ký, cọc, nhận phòng và active quá end_date.
- **Các bước:** chạy hai lần `php artisan contracts:process-lifecycle`.
- **Dữ liệu nhập:** không có.
- **Giao diện mong đợi:** dashboard có cảnh báo cần xử lý, không nhân đôi.
- **Database mong đợi:** mỗi loại cảnh báo/chu kỳ một bản ghi; expired chỉ có một transition.
- **Không được xảy ra:** xóa hợp đồng, xử lý cọc, giải phóng phòng hoặc khóa Client.
- **Khôi phục:** xử lý từng cảnh báo bằng action Admin.

### 4.29 Kiểm tra lịch sử trạng thái

- **Mục tiêu:** mọi thao tác truy vết được.
- **Điều kiện ban đầu:** một hợp đồng đã qua nhiều bước.
- **Các bước:** mở cuối trang chi tiết; đối chiếu với người thao tác và thời gian thực tế.
- **Dữ liệu nhập:** các lý do riêng dễ nhận biết ở mỗi action.
- **Giao diện mong đợi:** thứ tự đúng, hiển thị nguồn/đích/action/lý do/người thực hiện.
- **Database mong đợi:** mỗi transition đúng một history; metadata chứa dữ liệu cần audit; không có route xóa history.
- **Không được xảy ra:** thiếu history hoặc history nằm ngoài transaction khi action thất bại.
- **Khôi phục:** không xóa lịch sử.

## 5. Checklist kết thúc đợt test

- Chạy `php artisan contracts:audit-lifecycle`; lưu lại mọi dòng cần đối soát.
- Chạy `php artisan test` và xác nhận không có test lỗi/skipped ngoài chủ đích.
- Kiểm tra không có hợp đồng test active/expired đang giữ nhầm phòng dùng chung.
- Không xóa trực tiếp hợp đồng, history, invoice, payment hoặc utility reading để “dọn” kết quả.
