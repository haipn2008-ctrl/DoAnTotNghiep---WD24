<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [

            'Máy lạnh',

            'Máy giặt',

            'Wifi',

            'Tủ lạnh',

            'Nóng lạnh',

            'Ban công',

            'Bãi đỗ xe',

            'Thang máy',
        ];

        foreach ($amenities as $item) {

            Amenity::firstOrCreate([
                'name' => $item,
            ]);
        }
    }
}
