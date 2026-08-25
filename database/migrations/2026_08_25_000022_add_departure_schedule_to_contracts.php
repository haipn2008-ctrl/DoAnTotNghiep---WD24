<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_termination_requests', function (Blueprint $table): void {
            $table->string('request_type', 30)->default('early_termination')->index()->after('reason');
            $table->date('approved_end_date')->nullable()->after('admin_note');
            $table->dateTime('scheduled_checkout_at')->nullable()->index()->after('approved_end_date');
            $table->foreignId('processed_by')->nullable()->after('scheduled_checkout_at')->constrained('users')->nullOnDelete();
            $table->dateTime('fulfilled_at')->nullable()->after('processed_at');
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dateTime('scheduled_move_out_at')->nullable()->index()->after('actual_move_out_at');
            $table->foreignId('approved_termination_request_id')->nullable()->after('scheduled_move_out_at')
                ->constrained('contract_termination_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_termination_request_id');
            $table->dropColumn('scheduled_move_out_at');
        });
        Schema::table('contract_termination_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('processed_by');
            $table->dropColumn(['request_type', 'approved_end_date', 'scheduled_checkout_at', 'fulfilled_at']);
        });
    }
};
