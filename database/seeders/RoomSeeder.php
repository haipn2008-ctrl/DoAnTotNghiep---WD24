<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $assets = Amenity::query()->active()->assets()->orderBy('id')->take(4)->get();

            foreach (range(1, 3) as $floor) {
                foreach (range(1, 4) as $number) {
                    $room = Room::query()->updateOrCreate(
                        ['room_code' => sprintf('P%d%02d', $floor, $number)],
                        [
                            'floor' => $floor,
                            'price' => 3000000 + (($floor - 1) * 250000),
                            'area' => 22 + $number,
                            'max_people' => 3,
                            'current_people' => 0,
                            'status' => Room::STATUS_AVAILABLE,
                            'description' => 'Phòng trống sẵn sàng lập hợp đồng mới.',
                        ],
                    );

                    $room->amenities()->sync($assets->mapWithKeys(
                        fn (Amenity $asset): array => [$asset->id => [
                            'quantity' => 1,
                            'condition' => 'normal',
                            'note' => null,
                        ]],
                    )->all());
                }
            }
        });
    }
}
