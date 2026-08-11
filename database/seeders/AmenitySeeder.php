<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name' => 'Máy lạnh', 'is_quantifiable' => true],
            ['name' => 'Máy giặt', 'is_quantifiable' => true],
            ['name' => 'Wifi', 'is_quantifiable' => false],
            ['name' => 'Tủ lạnh', 'is_quantifiable' => true],
            ['name' => 'Nóng lạnh', 'is_quantifiable' => true],
            ['name' => 'Bãi đỗ xe', 'is_quantifiable' => false],
            ['name' => 'Giường', 'is_quantifiable' => true],
            ['name' => 'Bàn', 'is_quantifiable' => true],
            ['name' => 'Ghế', 'is_quantifiable' => true],
            ['name' => 'Tủ quần áo', 'is_quantifiable' => true],
        ];

        foreach ($amenities as $item) {
            Amenity::updateOrCreate(
                ['name' => $item['name']],
                ['is_quantifiable' => $item['is_quantifiable'], 'is_active' => true],
            );
        }
    }
}
