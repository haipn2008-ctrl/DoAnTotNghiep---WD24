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
            $table->string('category', 20)->default('asset')->after('description')->index();
        });

        DB::table('amenities')->where('is_quantifiable', false)->update(['category' => 'utility']);
        DB::table('amenities')->where('is_quantifiable', true)->update(['category' => 'asset']);

        $renames = [
            'Wifi' => 'Wi-Fi',
            'Bãi đỗ xe' => 'Chỗ để xe',
            'Nóng lạnh' => 'Bình nóng lạnh',
        ];

        foreach ($renames as $oldName => $newName) {
            if (! DB::table('amenities')->where('name', $newName)->exists()) {
                DB::table('amenities')->where('name', $oldName)->update([
                    'name' => $newName,
                    'updated_at' => now(),
                ]);
            }
        }

        $catalog = [
            ['name' => 'Wi-Fi', 'description' => 'Phòng được sử dụng mạng Wi-Fi của khu trọ.', 'category' => 'utility', 'is_quantifiable' => false],
            ['name' => 'Chỗ để xe', 'description' => 'Khu vực để xe dùng chung của khu trọ.', 'category' => 'utility', 'is_quantifiable' => false],
            ['name' => 'Nhà vệ sinh riêng', 'description' => 'Phòng có nhà vệ sinh sử dụng riêng.', 'category' => 'utility', 'is_quantifiable' => false],
            ['name' => 'Khu bếp riêng', 'description' => 'Phòng có khu vực nấu ăn sử dụng riêng.', 'category' => 'utility', 'is_quantifiable' => false],
            ['name' => 'Máy lạnh', 'description' => 'Máy lạnh được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Máy giặt', 'description' => 'Máy giặt được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Tủ lạnh', 'description' => 'Tủ lạnh được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Bình nóng lạnh', 'description' => 'Bình nóng lạnh được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Giường', 'description' => 'Giường được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Quạt', 'description' => 'Quạt được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Nệm', 'description' => 'Nệm được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Bàn', 'description' => 'Bàn được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Ghế', 'description' => 'Ghế được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Tủ quần áo', 'description' => 'Tủ quần áo được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
            ['name' => 'Bếp điện', 'description' => 'Bếp điện được bàn giao cùng phòng.', 'category' => 'asset', 'is_quantifiable' => true],
        ];

        foreach ($catalog as $item) {
            $query = DB::table('amenities')->where('name', $item['name']);
            $values = $item + ['is_active' => true, 'updated_at' => now()];

            if ($query->exists()) {
                $query->update($values);
            } else {
                DB::table('amenities')->insert($values + ['created_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
