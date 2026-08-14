<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Quy trình yêu cầu hoàn cọc
            $table->string('deposit_bank_name')->nullable()->after('deposit_process_note');
            $table->string('deposit_bank_account_number')->nullable()->after('deposit_bank_name');
            $table->string('deposit_bank_account_name')->nullable()->after('deposit_bank_account_number');
            $table->string('deposit_qr_image')->nullable()->after('deposit_bank_account_name');

            $table->timestamp('deposit_refund_requested_at')->nullable()->after('deposit_qr_image');
            $table->timestamp('deposit_refund_approved_at')->nullable()->after('deposit_refund_requested_at');

            $table->decimal('deposit_transfer_amount', 15, 2)->nullable()->after('deposit_refund_approved_at');
            $table->timestamp('deposit_transferred_at')->nullable()->after('deposit_transfer_amount');
            $table->string('deposit_transfer_proof')->nullable()->after('deposit_transferred_at');

            $table->text('deposit_admin_note')->nullable()->after('deposit_transfer_proof');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_bank_name',
                'deposit_bank_account_number',
                'deposit_bank_account_name',
                'deposit_qr_image',
                'deposit_refund_requested_at',
                'deposit_refund_approved_at',
                'deposit_transfer_amount',
                'deposit_transferred_at',
                'deposit_transfer_proof',
                'deposit_admin_note',
            ]);
        });
    }
};
