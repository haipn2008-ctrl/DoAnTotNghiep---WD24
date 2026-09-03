<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_transfers', function (Blueprint $table): void {
            $table->json('execution_payload')->nullable()->after('financial_snapshot');
        });

        Schema::table('contract_appendices', function (Blueprint $table): void {
            $table->foreignId('room_transfer_id')->nullable()->after('extension_request_id')
                ->constrained('room_transfers')->nullOnDelete();
            $table->unique('room_transfer_id');
        });
    }

    public function down(): void
    {
        Schema::table('contract_appendices', function (Blueprint $table): void {
            $table->dropUnique(['room_transfer_id']);
            $table->dropConstrainedForeignId('room_transfer_id');
        });
        Schema::table('room_transfers', function (Blueprint $table): void {
            $table->dropColumn('execution_payload');
        });
    }
};
