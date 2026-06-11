<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [

            [
                'room_code' => 'P101',
                'price' => 2500000,
                'area' => 20.5,
                'status' => 'occupied',
            ],
            [
                'room_code' => 'P102',
                'price' => 3000000,
                'area' => 25.0,
                'status' => 'occupied',
            ],

            [
                'room_code' => 'P103',
                'price' => 2000000,
                'area' => 18.0,
                'status' => 'available',
            ],
            [
                'room_code' => 'P104',
                'price' => 2200000,
                'area' => 18.0,
                'status' => 'maintenance',
            ],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['room_code' => $room['room_code']],

                [
                    'price'  => $room['price'],
                    'area'   => $room['area'],
                    'status' => $room['status'],
                ]
            );
        }
    }
}
