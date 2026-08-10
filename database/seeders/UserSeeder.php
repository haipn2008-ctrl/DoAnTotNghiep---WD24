<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleIds = Role::whereIn('role_name', ['Admin', 'User', 'Auditor'])
            ->pluck('id', 'role_name');

        $users = [
            ['name' => 'Nguyễn Minh Hoàng', 'email' => 'admin@nhatroanphuc.test', 'phone' => '0901000001', 'password' => 'Admin@123456', 'role' => 'Admin'],
            ['name' => 'Trần Thu Hà', 'email' => 'quanly@nhatroanphuc.test', 'phone' => '0901000002', 'password' => 'Admin@123456', 'role' => 'Admin'],
            ['name' => 'Phạm Gia Huy', 'email' => 'giahuy@example.test', 'phone' => '0912345601', 'password' => 'Tenant@123456', 'role' => 'User'],
            ['name' => 'Lê Ngọc Mai', 'email' => 'ngocmai@example.test', 'phone' => '0912345602', 'password' => 'Tenant@123456', 'role' => 'User'],
            ['name' => 'Vũ Đức Anh', 'email' => 'ducanh@example.test', 'phone' => '0912345603', 'password' => 'Tenant@123456', 'role' => 'User'],
            ['name' => 'Đặng Khánh Linh', 'email' => 'khanhlinh@example.test', 'phone' => '0912345604', 'password' => 'Tenant@123456', 'role' => 'User'],
            ['name' => 'Bùi Quang Nam', 'email' => 'quangnam@example.test', 'phone' => '0912345605', 'password' => 'Tenant@123456', 'role' => 'User'],
            ['name' => 'Đỗ Thanh Thảo', 'email' => 'thanhthao@example.test', 'phone' => '0912345606', 'password' => 'Tenant@123456', 'role' => 'User'],
            ['name' => 'Hoàng Tuấn Kiệt', 'email' => 'tuankiet@example.test', 'phone' => '0912345607', 'password' => 'Tenant@123456', 'role' => 'User'],
            ['name' => 'Ngô Phương Uyên', 'email' => 'phuonguyen@example.test', 'phone' => '0912345608', 'password' => 'Tenant@123456', 'role' => 'User'],

            // Manual QA accounts for AUTH-01 through AUTH-13. Password: Auth@123456.
            ['name' => 'AUTH Active Admin', 'email' => 'auth.admin@example.test', 'password' => 'Auth@123456', 'role' => 'Admin'],
            ['name' => 'AUTH Active Client', 'email' => 'auth.client@example.test', 'password' => 'Auth@123456', 'role' => 'User'],
            ['name' => 'AUTH Pending Client', 'email' => 'auth.pending@example.test', 'password' => 'Auth@123456', 'role' => 'User', 'status' => User::STATUS_PENDING],
            ['name' => 'AUTH Settling Client', 'email' => 'auth.settling@example.test', 'password' => 'Auth@123456', 'role' => 'User', 'status' => User::STATUS_SETTLING],
            ['name' => 'AUTH Locked Client', 'email' => 'auth.locked@example.test', 'password' => 'Auth@123456', 'role' => 'User', 'status' => User::STATUS_LOCKED],
            ['name' => 'AUTH Inactive Client', 'email' => 'auth.inactive@example.test', 'password' => 'Auth@123456', 'role' => 'User', 'status' => User::STATUS_INACTIVE],
            ['name' => 'AUTH Unsupported Role', 'email' => 'auth.unsupported-role@example.test', 'password' => 'Auth@123456', 'role' => 'Auditor'],
        ];

        foreach ($users as $userData) {
            $status = $userData['status'] ?? User::STATUS_ACTIVE;

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'phone' => $userData['phone'] ?? '0909'.str_pad((string) crc32($userData['email']) % 1000000, 6, '0', STR_PAD_LEFT),
                    'password' => Hash::make($userData['password']),
                    'role_id' => $roleIds->get($userData['role']),
                    'status' => $status,
                    'activated_at' => $status === User::STATUS_PENDING ? null : now(),
                    'last_login_at' => null,
                    'must_change_password' => $status === User::STATUS_PENDING,
                ]
            );
        }
    }
}
