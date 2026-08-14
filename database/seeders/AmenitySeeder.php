<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        Amenity::query()->utilities()->update(['is_active' => false]);

        $amenities = [
            ['name' => 'Máy lạnh', 'description' => 'Máy lạnh được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Máy giặt', 'description' => 'Máy giặt được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Tủ lạnh', 'description' => 'Tủ lạnh được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Bình nóng lạnh', 'description' => 'Bình nóng lạnh được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Giường', 'description' => 'Giường được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Bàn', 'description' => 'Bàn được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Ghế', 'description' => 'Ghế được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Tủ quần áo', 'description' => 'Tủ quần áo được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Quạt', 'description' => 'Quạt được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
            ['name' => 'Bếp điện', 'description' => 'Bếp điện được bàn giao cùng phòng.', 'category' => Amenity::CATEGORY_ASSET, 'is_quantifiable' => true],
        ];

        foreach ($amenities as $item) {
            Amenity::updateOrCreate(
                ['name' => $item['name']],
                $item + ['is_active' => true],
            );
        }
    }
}
