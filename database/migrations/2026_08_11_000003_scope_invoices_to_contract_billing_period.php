<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('room_id', 'invoices_room_id_index');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['room_id', 'month', 'year']);
            $table->unique(['contract_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['contract_id', 'month', 'year']);
            $table->unique(['room_id', 'month', 'year']);
            $table->dropIndex('invoices_room_id_index');
        });
    }
};
