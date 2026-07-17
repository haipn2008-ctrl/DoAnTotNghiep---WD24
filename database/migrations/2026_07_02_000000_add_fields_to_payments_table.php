<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'bank_transfer', 'qr'])
                    ->default('cash')
                    ->after('payment_date');
            }

            if (! Schema::hasColumn('payments', 'transaction_code')) {
                $table->string('transaction_code')
                    ->nullable()
                    ->after('payment_method');
            }

            if (! Schema::hasColumn('payments', 'status')) {
                $table->enum('status', ['pending', 'success', 'failed'])
                    ->default('success')
                    ->after('transaction_code');
            }

            if (! Schema::hasColumn('payments', 'confirmed_by')) {
                $table->foreignId('confirmed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'confirmed_by')) {
                $table->dropForeign(['confirmed_by']);
                $table->dropColumn('confirmed_by');
            }
            if (Schema::hasColumn('payments', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('payments', 'transaction_code')) {
                $table->dropColumn('transaction_code');
            }
            if (Schema::hasColumn('payments', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
