<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_quantifiable');
        });

        // Không xóa dữ liệu cũ: chỉ ẩn hai mục không còn sử dụng khỏi giao diện và request mới.
        DB::table('amenities')
            ->whereIn('name', ['Ban công', 'Thang máy'])
            ->update(['is_active' => false, 'updated_at' => now()]);

        // Chuẩn hóa ba trạng thái cũ thành hai trạng thái dễ hiểu hơn.
        DB::table('amenity_room')
            ->whereIn('condition', ['good', 'worn'])
            ->update(['condition' => 'normal', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('amenity_room')
            ->where('condition', 'normal')
            ->update(['condition' => 'good', 'updated_at' => now()]);

        Schema::table('amenities', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
