<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contracts')->update([
            'service_enabled' => true,
            'parking_vehicle_type' => null,
            'parking_quantity' => 0,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Không thể khôi phục chính xác lựa chọn dịch vụ và xe đã lưu trùng trước đây.
    }
};
