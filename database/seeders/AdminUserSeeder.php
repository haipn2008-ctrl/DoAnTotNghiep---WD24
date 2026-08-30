<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = Role::query()->where('role_name', 'Admin')->value('id');

        foreach ([
            ['Nguyễn Minh Hoàng', 'admin@nhatroanphuc.test', '0901000001'],
            ['Trần Thu Hà', 'quanly@nhatroanphuc.test', '0901000002'],
        ] as [$name, $email, $phone]) {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'password' => Hash::make('Admin@123456'),
                    'role_id' => $adminRoleId,
                    'status' => User::STATUS_ACTIVE,
                    'activated_at' => now(),
                    'last_login_at' => null,
                    'must_change_password' => false,
                ],
            );
        }
    }
}
