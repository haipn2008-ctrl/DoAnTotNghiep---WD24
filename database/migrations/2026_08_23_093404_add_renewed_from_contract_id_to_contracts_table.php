<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('renewed_from_contract_id')
                ->nullable()
                ->after('id')
                ->constrained('contracts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['renewed_from_contract_id']);
            $table->dropColumn('renewed_from_contract_id');
        });
    }
};