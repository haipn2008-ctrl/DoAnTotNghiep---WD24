<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo index riêng cho room_id để foreign key
        // không phụ thuộc vào composite unique cũ.
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('room_id', 'invoices_room_id_index');
        });

        // Unique mới:
        // Một hợp đồng chỉ có tối đa một hóa đơn
        // cho cùng tháng + năm + loại hóa đơn.
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(
                ['contract_id', 'month', 'year', 'invoice_type'],
                'invoices_contract_month_year_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(
                'invoices_contract_month_year_type_unique'
            );
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(
                'invoices_room_id_index'
            );
        });
    }
};
