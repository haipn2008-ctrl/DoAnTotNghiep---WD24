<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_termination_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            // Ngày khách muốn trả phòng
            $table->date('requested_end_date');

            // Lý do trả phòng
            $table->text('reason')->nullable();

            // pending / approved / rejected
            $table->string('status')->default('pending');

            // Ghi chú của admin khi xử lý
            $table->text('admin_note')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_termination_requests');
    }
};
