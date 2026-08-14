<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('amenities')->where('name', 'Chỗ để xe')->update([
            'description' => 'Khu vực để xe dùng chung của khu trọ.',
            'updated_at' => now(),
        ]);

        $roomIds = DB::table('rooms')->pluck('id');
        $utilityIds = DB::table('amenities')
            ->where('category', 'utility')
            ->where('is_active', true)
            ->pluck('id');

        foreach ($roomIds as $roomId) {
            foreach ($utilityIds as $utilityId) {
                DB::table('amenity_room')->insertOrIgnore([
                    'room_id' => $roomId,
                    'amenity_id' => $utilityId,
                    'quantity' => 1,
                    'condition' => 'normal',
                    'note' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Không gỡ tiện ích khỏi phòng khi rollback để tránh làm mất dữ liệu đang được sử dụng.
    }
};
