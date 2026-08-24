<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contracts')->update([
            'internet_enabled' => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Không thể khôi phục chính xác lựa chọn Internet cũ của từng hợp đồng.
    }
};
