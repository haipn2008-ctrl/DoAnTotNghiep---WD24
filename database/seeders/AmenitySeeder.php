<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Amenity;

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
                'name' => $item
            ]);
        }
    }
}
