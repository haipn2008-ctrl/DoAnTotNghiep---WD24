<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Bộ dữ liệu khởi tạo sạch cho ứng dụng.
     *
     * Chỉ tạo dữ liệu nền; không tạo hợp đồng, hóa đơn, thanh toán,
     * yêu cầu duyệt, thông báo hoặc tình huống kiểm thử nghiệp vụ.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            AvailableTenantSeeder::class,
            AmenitySeeder::class,
            RoomSeeder::class,
            SettingSeeder::class,
            DemoScenarioSeeder::class,
            RecentPaidContractsSeeder::class,
            DefaultRoomAssetsSeeder::class,
            GovernmentUtilityExpenseSeeder::class,
        ]);
    }
}
