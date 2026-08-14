<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('invoices'));

        // Chỉ xóa unique cũ nếu nó thực sự tồn tại
        if ($indexes->contains(fn ($index) =>
            $index['name'] === 'invoices_room_id_month_year_unique'
        )) {
            // MySQL needs a separate room_id index before the composite unique
            // index can be removed because the foreign key currently uses it.
            if (! $indexes->contains(fn ($index) =>
                $index['name'] === 'invoices_room_id_index'
            )) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->index('room_id', 'invoices_room_id_index');
                });
            }

            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_room_id_month_year_unique');
            });
        }

        // Chỉ tạo unique mới nếu chưa tồn tại
        if (!$indexes->contains(fn ($index) =>
            $index['name'] === 'invoices_contract_id_month_year_unique'
        )) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unique(
                    ['contract_id', 'month', 'year'],
                    'invoices_contract_id_month_year_unique'
                );
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('invoices'));

        if ($indexes->contains(fn ($index) =>
            $index['name'] === 'invoices_contract_id_month_year_unique'
        )) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_contract_id_month_year_unique');
            });
        }

        if (!$indexes->contains(fn ($index) =>
            $index['name'] === 'invoices_room_id_month_year_unique'
        )) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unique(
                    ['room_id', 'month', 'year'],
                    'invoices_room_id_month_year_unique'
                );
            });
        }

        $indexes = collect(Schema::getIndexes('invoices'));
        if ($indexes->contains(fn ($index) =>
            $index['name'] === 'invoices_room_id_index'
        )) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex('invoices_room_id_index');
            });
        }
    }
};
