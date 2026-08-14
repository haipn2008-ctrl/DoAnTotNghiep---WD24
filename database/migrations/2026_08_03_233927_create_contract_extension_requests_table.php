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
        Schema::create('contract_extension_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')
                ->constrained('contracts')
                ->cascadeOnDelete();

            // Ngày kết thúc hiện tại của hợp đồng
            $table->date('current_end_date');

            // Ngày khách muốn gia hạn tới
            $table->date('requested_end_date');

            // Lý do khách yêu cầu gia hạn
            $table->text('reason')->nullable();

            // pending | approved | rejected
            $table->string('status')->default('pending');

            // Ghi chú/phản hồi của admin
            $table->text('admin_note')->nullable();

            // Thời điểm admin xử lý
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_extension_requests');
    }
};
