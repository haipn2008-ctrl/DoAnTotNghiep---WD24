<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Đồng bộ deposit_status với các trạng thái mà Contract model
        // và DepositRefundController đang sử dụng.
        DB::statement("
            ALTER TABLE contracts
            MODIFY deposit_status ENUM(
                'pending',
                'paid',
                'refund_requested',
                'refund_approved',
                'refund_rejected',
                'refund_processing',
                'returned',
                'partial_returned',
                'forfeited'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        // Chỉ rollback được khi dữ liệu hiện tại không còn các trạng thái
        // mới như refund_approved, refund_requested...
        DB::statement("
            ALTER TABLE contracts
            MODIFY deposit_status ENUM(
                'pending',
                'paid',
                'returned',
                'partial_returned'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
};