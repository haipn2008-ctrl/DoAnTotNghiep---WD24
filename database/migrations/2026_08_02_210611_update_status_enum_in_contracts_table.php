<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE contracts
            MODIFY COLUMN status ENUM(
                'draft',
                'pending_signature',
                'signed',
                'deposit_paid',
                'active',
                'expired',
                'terminated',
                'deposit_returned'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE contracts
            MODIFY COLUMN status ENUM(
                'draft',
                'pending_signature',
                'signed',
                'active',
                'expired',
                'terminated',
                'deposit_returned'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};
