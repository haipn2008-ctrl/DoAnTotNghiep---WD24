<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AvailableTenantSeeder extends Seeder
{
    public function run(): void
    {
        $userRoleId = Role::query()->where('role_name', 'User')->value('id');

        $profiles = [
            ['Nguyễn Hoàng Anh', 'nguyenhoanganh.demo@gmail.com', '0936102001', '1996-04-15', 'male', '001096012345', '2021-06-18', 'Cục Cảnh sát QLHC về TTXH', 'Số 18 phố Nguyễn Khánh Toàn, Cầu Giấy, Hà Nội'],
            ['Trần Bảo Ngọc', 'tranbaongoc.demo@gmail.com', '0936102002', '1999-09-22', 'female', '001299023456', '2022-03-11', 'Cục Cảnh sát QLHC về TTXH', 'Số 42 phố Vũ Trọng Phụng, Thanh Xuân, Hà Nội'],
            ['Phạm Minh Đức', 'phamminhduc.demo@gmail.com', '0936102003', '1998-01-08', 'male', '038098034567', '2020-10-26', 'Công an tỉnh Quảng Ninh', 'Phường Hồng Hải, thành phố Hạ Long, Quảng Ninh'],
            ['Lê Thu Trang', 'lethutrang.demo@gmail.com', '0936102004', '1997-07-19', 'female', '031197045678', '2021-12-09', 'Công an thành phố Hải Phòng', 'Phường Đằng Giang, quận Ngô Quyền, Hải Phòng'],
            ['Vũ Khánh Toàn', 'vukhanhtoan.demo@gmail.com', '0936102005', '2000-11-03', 'male', '036300056789', '2023-04-17', 'Cục Cảnh sát QLHC về TTXH', 'Phường Mỹ Xá, thành phố Nam Định, Nam Định'],
            ['Đặng Ngọc Ánh', 'dangngocanh.demo@gmail.com', '0936102006', '1999-02-27', 'female', '037199067890', '2022-08-05', 'Công an tỉnh Ninh Bình', 'Phường Nam Bình, thành phố Ninh Bình, Ninh Bình'],
            ['Bùi Tiến Dũng', 'buitiendung.demo@gmail.com', '0936102007', '1998-12-12', 'male', '033098078901', '2021-02-22', 'Công an tỉnh Hưng Yên', 'Thị trấn Văn Giang, huyện Văn Giang, Hưng Yên'],
            ['Đỗ Mai Phương', 'domaiphuong.demo@gmail.com', '0936102008', '2001-05-30', 'female', '034301089012', '2023-09-14', 'Cục Cảnh sát QLHC về TTXH', 'Phường Trần Hưng Đạo, thành phố Hải Dương, Hải Dương'],
            ['Hoàng Quốc Việt', 'hoangquocviet.demo@gmail.com', '0936102009', '1995-08-16', 'male', '030095090123', '2020-05-07', 'Công an tỉnh Hải Dương', 'Phường Sao Đỏ, thành phố Chí Linh, Hải Dương'],
            ['Ngô Thanh Hương', 'ngothanhhuong.demo@gmail.com', '0936102010', '2000-03-24', 'female', '035200101234', '2022-11-28', 'Cục Cảnh sát QLHC về TTXH', 'Phường Gia Cẩm, thành phố Việt Trì, Phú Thọ'],
        ];

        DB::transaction(function () use ($profiles, $userRoleId): void {
            foreach ($profiles as [$name, $email, $phone, $birthDate, $gender, $cccd, $issueDate, $issuePlace, $address]) {
                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'phone' => $phone,
                        'password' => Hash::make('Tenant@123456'),
                        'role_id' => $userRoleId,
                        'status' => User::STATUS_ACTIVE,
                        'activated_at' => now(),
                        'last_login_at' => null,
                        'must_change_password' => false,
                    ]
                );

                Tenant::query()->updateOrCreate(
                    ['cccd' => $cccd],
                    [
                        'user_id' => $user->id,
                        'full_name' => $name,
                        'date_of_birth' => $birthDate,
                        'gender' => $gender,
                        'cccd_issue_date' => $issueDate,
                        'cccd_issue_place' => $issuePlace,
                        'phone' => $phone,
                        'email' => $email,
                        'address' => $address,
                    ]
                );
            }

            // Dọn đúng các tài khoản mẫu cũ do seeder trước đây tạo bằng miền
            // example.test. Chỉ xóa bản ghi đã mồ côi, không đụng tới tài khoản
            // đang gắn với hồ sơ hoặc dữ liệu nghiệp vụ.
            User::query()
                ->whereIn('email', [
                    'hoanganh.nguyen@example.test',
                    'baongoc.tran@example.test',
                    'minhduc.pham@example.test',
                    'thutrang.le@example.test',
                    'khanhtoan.vu@example.test',
                    'ngocanh.dang@example.test',
                    'tiendung.bui@example.test',
                    'maiphuong.do@example.test',
                    'quocviet.hoang@example.test',
                    'thanhhuong.ngo@example.test',
                ])
                ->whereDoesntHave('tenant')
                ->delete();
        });
    }
}
