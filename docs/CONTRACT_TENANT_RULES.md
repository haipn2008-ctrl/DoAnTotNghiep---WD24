# Quy tắc người thuê trong hợp đồng

## Mô hình nghiệp vụ

Mọi cá nhân trong danh sách của một hợp đồng đều có tư cách người thuê như nhau. Mỗi cá nhân có:

- một hồ sơ tại bảng `tenants`;
- một tư cách tham gia hợp đồng tại bảng `contract_tenants`;
- ngày sinh, CCCD và số điện thoại riêng;
- trạng thái tham gia được lưu lịch sử tại `contract_tenant_histories`.

## Tuổi tối thiểu

Người đại diện và mọi người thuê thành viên phải đủ 18 tuổi tại ngày khai báo. Quy tắc được kiểm tra ở cả luồng quản trị, kích hoạt tài khoản, cập nhật hồ sơ và khai báo thành viên từ cổng khách thuê.

## Vai trò

| Giá trị `role` | Ý nghĩa |
| --- | --- |
| `representative` | Người thuê đại diện và đầu mối chính của hợp đồng |
| `tenant` | Người thuê thành viên |

Người đại diện cũng là người thuê trực tiếp và được tính vào sức chứa phòng. Mỗi hợp đồng phải có một bản ghi `representative` liên kết đúng hồ sơ người thuê chính của hợp đồng.

## Tài khoản và quyền truy cập

- Chỉ người đại diện bắt buộc có tài khoản để làm việc với hợp đồng.
- Người thuê thành viên không được tự động cấp tài khoản.
- Nếu một thành viên đã có tài khoản từ nghiệp vụ khác, tài khoản đó vẫn không được xem hoặc quản lý hợp đồng của người đại diện.
- Danh sách hợp đồng, tệp hợp đồng, xác nhận bàn giao và thao tác khai báo thành viên đều kiểm tra người đại diện ở backend.

Ví dụ: ba người cùng thuê một phòng tạo ba hồ sơ `tenants` và ba dòng `contract_tenants`, nhưng mặc định chỉ có một tài khoản quản lý hợp đồng.

## Bảo toàn lịch sử

Không xóa cứng tư cách thuê đã khai báo. Khi thay đổi danh sách, từ chối, rút khai báo hoặc rời phòng, hệ thống chuyển trạng thái và tạo lịch sử. Điều này giữ được dữ liệu phục vụ đối soát hợp đồng.

## Thành phần mã nguồn chính

- Model: `App\Models\ContractTenant` và `App\Models\ContractTenantHistory`.
- Nghiệp vụ: `App\Services\ContractTenantService`.
- Quan hệ hợp đồng: `Contract::members()`.
- Quan hệ hồ sơ: `Tenant::contractMemberships()` và `Tenant::memberContracts()`.
- Route quản trị: `admin.contract-tenants.*`.
- Route khách thuê: `client.contracts.members.*`.

Migration `2026_08_24_000007_rename_contract_occupants_to_contract_tenants.php` chuyển cấu trúc lịch sử sang tên hiện hành. Không sửa các migration đã được triển khai trước đó.
