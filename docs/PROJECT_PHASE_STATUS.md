# Trạng thái các giai đoạn của dự án

Cập nhật ngày 25/08/2026.

## Nguyên tắc chung

- Khách mới bắt buộc sử dụng cổng khách thuê.
- Admin cấp tài khoản; khách đăng nhập lần đầu để kích hoạt và hoàn thiện hồ sơ.
- Kích hoạt tài khoản không đồng nghĩa với đang thuê phòng.
- Chỉ khi hoàn tất hợp đồng và admin xác nhận bàn giao thì hợp đồng mới chuyển
  sang hoạt động và khách mới được tính là đang thuê.
- Luồng tạo khách thuê thủ công không thuộc quy trình chính; chỉ giữ làm phương
  án xử lý dữ liệu cũ hoặc sự cố đặc biệt nếu còn cần.

## Giai đoạn 1 — Đã hoàn thành

Phạm vi từ lúc chuẩn bị khách mới đến khi bàn giao phòng:

- Cấp và kích hoạt tài khoản khách thuê.
- Hoàn thiện thông tin cá nhân, liên hệ và giấy tờ định danh.
- Tạo hợp đồng, khai báo người thuê đại diện và thành viên cùng thuê.
- Kiểm soát sức chứa phòng và tính duy nhất của thông tin định danh/liên hệ.
- Xử lý tiền cọc, ký hợp đồng và xác nhận thông tin nhận phòng.
- Ghi chỉ số gốc của phòng và chỉ số bàn giao theo hợp đồng.
- Admin xác nhận bàn giao; cập nhật hợp đồng, phòng và người đang thuê một cách
  đồng bộ.

Mốc quan trọng: chỉ số `baseline` là dữ liệu nội bộ của phòng, còn chỉ số
`handover` thuộc hợp đồng và được khách nhìn thấy với mức tiêu thụ bằng 0 tại
thời điểm nhận phòng.

## Giai đoạn 2 — Đã hoàn thành

Phạm vi quản lý trong thời gian hợp đồng đang hoạt động:

- Khách xem đúng phòng, hợp đồng và dữ liệu thuộc phạm vi thuê của mình.
- Ghi, xác nhận và khóa chỉ số điện nước định kỳ; không để dữ liệu người thuê
  trước hoặc baseline lọt vào cổng khách.
- Sinh, phát hành và xem hóa đơn; bảo toàn hóa đơn đã phát hành bằng phiếu điều
  chỉnh thay vì sửa lịch sử.
- Thanh toán đủ, thanh toán một phần và nhiều lần; khách gửi biên lai, admin
  duyệt hoặc từ chối ngay tại chi tiết hóa đơn.
- Theo dõi công nợ và gửi nhắc thanh toán bằng thông báo nội bộ. Không sử dụng
  lựa chọn Zalo, điện thoại hay email làm kênh nhắc trong quy trình hệ thống.
- Khách nhận thông báo bằng biểu tượng chuông, xem trạng thái mới/đã đọc và mở
  đúng hóa đơn liên quan.
- Quản lý phương tiện, ảnh phương tiện riêng tư và giới hạn chỗ để xe.
- Tiếp nhận và xử lý yêu cầu hỗ trợ của khách.
- Admin quản lý hồ sơ tạm trú; hồ sơ đã ký không thể sửa hoặc ký đè, hồ sơ hủy
  được giữ lịch sử và lý do để truy vết.
- Tài liệu hợp đồng, biên lai, ảnh đồng hồ và ảnh phương tiện được phục vụ qua
  route có kiểm tra quyền thay vì công khai trực tiếp.

## Giai đoạn 3 — Đang hoàn thiện

Phạm vi chưa được tuyên bố hoàn thành:

- Yêu cầu và phê duyệt gia hạn hợp đồng.
- Yêu cầu trả phòng, chốt ngày rời đi và chốt chỉ số cuối.
- Hóa đơn quyết toán, xử lý công nợ cuối kỳ và hoàn/khấu trừ tiền cọc.
- Bàn giao lại phòng, kết thúc tư cách thuê và cập nhật trạng thái phòng.
- Kiểm tra tính bất biến, truy vết và các trường hợp thất bại của toàn bộ luồng
  gia hạn/trả phòng.

Các chức năng giai đoạn 3 có thể đã có mã nguồn hoặc test riêng lẻ, nhưng vẫn
được xem là đang phát triển cho đến khi hoàn tất kiểm thử thủ công đầu-cuối.

## Mốc kiểm thử hiện tại

Sau mỗi thay đổi ở giai đoạn 3 phải bảo đảm các test của giai đoạn 1 và 2 tiếp
tục đạt. Không thay đổi các nguyên tắc đã chốt ở trên nếu chưa cập nhật tài liệu
và bổ sung test hồi quy tương ứng.
