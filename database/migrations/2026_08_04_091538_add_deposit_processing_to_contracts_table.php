<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('deposit_process_type', 30)->nullable()->after('deposit_paid_at');
            $table->decimal('deposit_refund_amount', 15, 2)->nullable()->after('deposit_process_type');
            $table->decimal('deposit_deduction_amount', 15, 2)->nullable()->after('deposit_refund_amount');
            $table->timestamp('deposit_processed_at')->nullable()->after('deposit_deduction_amount');
            $table->string('deposit_process_reason', 255)->nullable()->after('deposit_processed_at');
            $table->text('deposit_process_note')->nullable()->after('deposit_process_reason');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['deposit_process_type','deposit_refund_amount','deposit_deduction_amount','deposit_processed_at','deposit_process_reason','deposit_process_note']);
        });
    }
};
