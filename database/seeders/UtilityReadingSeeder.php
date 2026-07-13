<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UtilityReading;
use Carbon\Carbon;

class UtilityReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();

        $utilityData = [
            // ==========================================
            // PHÒNG 1 (room_id = 1) - Bắt đầu 01/06/2026
            // ==========================================
            
            // Tháng 5/2026: Số gốc trước khi khách vào ở
            [
                'room_id'         => 1,
                'month'           => 5,
                'year'            => 2026,
                'electricity_old' => 0,
                'electricity_new' => 1200,
                'water_old'       => 0,
                'water_new'       => 150,
                'status'          => 'confirmed',
                'created_at'      => clone $now,
                'updated_at'      => clone $now,
            ],
            // Tháng 6/2026: Tháng sử dụng đầu tiên
            [
                'room_id'         => 1,
                'month'           => 6,
                'year'            => 2026,
                'electricity_old' => 1200,
                'electricity_new' => 1350, // Dùng 150 kWh
                'water_old'       => 150,
                'water_new'       => 162,  // Dùng 12 Khối
                'status'          => 'confirmed',
                'created_at'      => clone $now,
                'updated_at'      => clone $now,
            ],

            // ==========================================
            // PHÒNG 2 (room_id = 2) - Bắt đầu 01/04/2026
            // ==========================================
            
            // Tháng 3/2026: Số gốc trước khi khách vào ở
            [
                'room_id'         => 2,
                'month'           => 3,
                'year'            => 2026,
                'electricity_old' => 0,
                'electricity_new' => 3000,
                'water_old'       => 0,
                'water_new'       => 400,
                'status'          => 'confirmed',
                'created_at'      => clone $now,
                'updated_at'      => clone $now,
            ],
            // Tháng 4/2026: Tháng sử dụng đầu tiên
            [
                'room_id'         => 2,
                'month'           => 4,
                'year'            => 2026,
                'electricity_old' => 3000,
                'electricity_new' => 3180, // Dùng 180 kWh
                'water_old'       => 400,
                'water_new'       => 415,  // Dùng 15 Khối
                'status'          => 'confirmed',
                'created_at'      => clone $now,
                'updated_at'      => clone $now,
            ],
            // Tháng 5/2026
            [
                'room_id'         => 2,
                'month'           => 5,
                'year'            => 2026,
                'electricity_old' => 3180,
                'electricity_new' => 3400, // Dùng 220 kWh
                'water_old'       => 415,
                'water_new'       => 432,  // Dùng 17 Khối
                'status'          => 'confirmed',
                'created_at'      => clone $now,
                'updated_at'      => clone $now,
            ],
            // Tháng 6/2026
            [
                'room_id'         => 2,
                'month'           => 6,
                'year'            => 2026,
                'electricity_old' => 3400,
                'electricity_new' => 3650, // Dùng 250 kWh
                'water_old'       => 432,
                'water_new'       => 450,  // Dùng 18 Khối
                'status'          => 'confirmed',
                'created_at'      => clone $now,
                'updated_at'      => clone $now,
            ],
        ];

        // Insert dữ liệu vào DB (dùng insert để chạy nhanh hơn create)
        UtilityReading::insert($utilityData);
    }
}
