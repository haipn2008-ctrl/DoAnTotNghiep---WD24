<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('amenities')->where('name', 'Nệm')->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('amenities')->where('name', 'Nệm')->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);
    }
};
