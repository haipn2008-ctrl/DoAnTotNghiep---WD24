<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $utilityIds = DB::table('amenities')->where('category', 'utility')->pluck('id');

        DB::table('amenity_room')->whereIn('amenity_id', $utilityIds)->delete();
        DB::table('amenities')->whereIn('id', $utilityIds)->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $utilityIds = DB::table('amenities')->where('category', 'utility')->pluck('id');
        DB::table('amenities')->whereIn('id', $utilityIds)->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);

        foreach (DB::table('rooms')->pluck('id') as $roomId) {
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
};
