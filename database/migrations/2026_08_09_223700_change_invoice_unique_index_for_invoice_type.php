<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            // Tạm bỏ foreign key room_id
            $table->dropForeign(['room_id']);

            // Xóa unique cũ
            $table->dropUnique(['room_id', 'month', 'year']);

            // Tạo unique mới
            $table->unique(
                ['room_id', 'month', 'year', 'invoice_type'],
                'invoices_room_month_year_type_unique'
            );

            // Tạo lại foreign key
            $table->foreign('room_id')
                ->references('id')
                ->on('rooms')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_room_month_year_type_unique');

            $table->unique(
                ['room_id', 'month', 'year'],
                'invoices_room_month_year_unique'
            );
        });
    }
};
