<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            // 1. Tạo index riêng cho room_id
            // để foreign key invoices_room_id_foreign
            // không còn phụ thuộc vào composite unique cũ.
            $table->index('room_id', 'invoices_room_id_index');
        });

        Schema::table('invoices', function (Blueprint $table) {

            // 2. Bây giờ mới xóa unique cũ
            $table->dropUnique('invoices_room_month_year_type_unique');
        });

    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(
                ['room_id', 'month', 'year', 'invoice_type'],
                'invoices_room_month_year_type_unique'
            );

            $table->dropIndex('invoices_room_id_index');
        });
    }
};
