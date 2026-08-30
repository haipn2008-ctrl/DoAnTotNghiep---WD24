<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->timestamp('deposit_receipt_confirmation_due_at')->nullable()->after('deposit_transfer_proof');
            $table->timestamp('deposit_receipt_confirmed_at')->nullable()->after('deposit_receipt_confirmation_due_at');
            $table->string('deposit_receipt_confirmation_source', 20)->nullable()->after('deposit_receipt_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_receipt_confirmation_due_at',
                'deposit_receipt_confirmed_at',
                'deposit_receipt_confirmation_source',
            ]);
        });
    }
};
