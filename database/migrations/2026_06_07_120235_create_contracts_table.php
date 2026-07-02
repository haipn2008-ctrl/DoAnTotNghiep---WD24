<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration: Tạo cấu trúc bảng contracts
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {

            // Khóa chính
            $table->id();

            // Mã hợp đồng - Unique để tránh trùng lặp
            $table->string('contract_code')->unique();

            // Quan hệ với bảng rooms (Phòng thuê)
            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnUpdate() // Cập nhật room_id thì update theo
                ->restrictOnDelete(); // Không cho xóa phòng nếu đang có hợp đồng

            // Quan hệ với bảng tenants (Người thuê chính)
            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Người đại diện (VD: ký thay, thanh toán thay)
            $table->foreignId('representative_tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete(); // Nếu xóa tenant đại diện, cột này về NULL

            // Thông tin tài chính
            $table->decimal('monthly_rent', 12, 2); // Giá thuê mỗi tháng
            $table->decimal('deposit_amount', 12, 2)->default(0); // Tiền cọc

            // Trạng thái tiền cọc
            $table->enum('deposit_status', [
                'pending', 'paid', 'returned'
            ])->default('pending');
            $table->timestamp('deposit_paid_at')->nullable(); // Thời điểm đóng cọc

            // Thông tin vận hành
            $table->integer('number_of_people')->default(1); // Số người ở
            $table->timestamp('signed_at')->nullable();     // Ngày ký kết hợp đồng
            $table->date('start_date');                     // Ngày bắt đầu hiệu lực
            $table->date('end_date');                       // Ngày kết thúc dự kiến
            $table->date('actual_end_date')->nullable();    // Ngày thực tế trả phòng

            // Thông tin gia hạn (Extension)
            $table->timestamp('extended_at')->nullable();
            $table->date('extend_start_date')->nullable();
            $table->date('extend_end_date')->nullable();
            $table->string('extend_reason')->nullable();
            $table->text('extend_note')->nullable();

            // Thông tin chấm dứt (Termination)
            $table->timestamp('terminated_at')->nullable();
            $table->enum('terminated_by', ['admin', 'tenant'])->nullable();
            $table->string('termination_reason')->nullable();
            $table->text('termination_note')->nullable();

            // File đính kèm
            $table->string('contract_file')->nullable();

            // Trạng thái hợp đồng - Vòng đời của hợp đồng
            $table->enum('status', [
                'draft',
                'pending_signature',
                'signed',
                'active',
                'expired',
                'terminated'
            ])->default('draft');

            // Ghi chú chung & Timestamps (created_at, updated_at)
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Hủy migration: Xóa bảng contracts khi rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
