# Dữ liệu kiểm thử nghiệp vụ

Chạy lại toàn bộ dữ liệu:

```bash
php artisan migrate:fresh --seed
```

## Tài khoản

- Quản trị viên: `admin@nhatroanphuc.test` / `Admin@123456`
- Khách QA-01: `qa.client.a@example.test` / `Test@123456`
- Khách QA-02: `qa.client.b@example.test` / `Test@123456`
- Khách QA-03: `qa.client.c@example.test` / `Test@123456`
- Các khách còn lại: email hiển thị trong hồ sơ / `Tenant@123456`

Tài khoản nhận tiền được giữ nguyên: MB — `6666200066789` — `NGUYEN XUAN NAM`.

## Hợp đồng và tiền cọc

| Phòng | Tình huống |
|---|---|
| QA-01 | Hợp đồng bản nháp |
| QA-02 | Chờ khách ký |
| QA-03 | Đã ký, chờ đóng cọc |
| QA-04 | Đã đóng cọc, chờ nhận phòng |
| QA-05 | Đang ở, hóa đơn chưa thanh toán |
| QA-06 | Đang ở, hóa đơn thanh toán một phần |
| QA-07 | Đang ở, hóa đơn đã thanh toán |
| QA-08 | Hết hạn nhưng khách vẫn đang ở; có hóa đơn xóa nợ |
| QA-09 | Đã trả phòng, cần xử lý tiền cọc |
| QA-10 | Khách đã yêu cầu hoàn cọc |
| QA-11 | Quản trị viên đã duyệt hoàn cọc |
| QA-12 | Đang chuyển khoản hoàn cọc |
| QA-13 | Hoàn tất và đã hoàn đủ cọc |
| QA-14 | Hoàn tất và có khấu trừ cọc do tài sản hỏng |
| QA-15 | Hoàn tất và giữ lại tiền cọc |
| QA-16 | Hợp đồng đã hủy |

## Phòng độc lập

- `QA-TRONG`: phòng mới/trống, có chỉ số điện nước nền.
- `QA-BAO-TRI`: phòng đang bảo trì.

## Các dữ liệu khác

- Xe: đủ trạng thái chờ duyệt, đã duyệt và bị từ chối; mỗi xe gắn với một chủ xe.
- Người thuê: đủ trạng thái chờ duyệt, đã duyệt, đang ở, đã rời phòng, bị từ chối và đã rút khai báo.
- Thanh toán: đủ chờ duyệt, thành công, thất bại; tiền mặt, chuyển khoản và QR.
- Yêu cầu gia hạn/trả phòng: đủ chờ duyệt, đã duyệt và bị từ chối.
- Hỗ trợ và tạm trú: đủ toàn bộ trạng thái hiện có.
- Hóa đơn tiền phòng, điện, nước, Internet và dịch vụ của tháng trước được lập vào ngày 05. Internet thu một lần theo phòng với mức cấu hình hiện hành; không tạo hóa đơn tiền phòng tháng đầu trước khi nhận phòng.
