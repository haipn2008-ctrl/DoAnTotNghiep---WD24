<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'room_id')) {
            Schema::table('invoices', function (Blueprint $table) {

                $table->foreignId('room_id')
                    ->nullable()
                    ->after('contract_id')
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $table->unique([
                    'room_id',
                    'month',
                    'year'
                ]);
            });
        }
    }
    //

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_room_month_year_unique');
            $table->dropConstrainedForeignId('room_id');
        });
    }
};
