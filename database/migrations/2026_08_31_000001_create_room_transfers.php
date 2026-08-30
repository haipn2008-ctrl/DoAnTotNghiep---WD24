<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_transfers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_room_id')->constrained('rooms')->restrictOnDelete();
            $table->foreignId('new_room_id')->constrained('rooms')->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20);
            $table->date('requested_transfer_date');
            $table->date('effective_date')->nullable();
            $table->text('reason');
            $table->string('status', 30)->default('pending')->index();
            $table->text('admin_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('old_checkout_reading_id')->nullable()->constrained('utility_readings')->nullOnDelete();
            $table->foreignId('new_handover_reading_id')->nullable()->constrained('utility_readings')->nullOnDelete();
            $table->foreignId('transfer_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('deposit_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->decimal('outstanding_amount', 15, 2)->default(0);
            $table->decimal('old_monthly_rent', 15, 2)->nullable();
            $table->decimal('new_monthly_rent', 15, 2)->nullable();
            $table->decimal('old_deposit_amount', 15, 2)->nullable();
            $table->decimal('new_deposit_amount', 15, 2)->nullable();
            $table->decimal('deposit_difference', 15, 2)->default(0);
            $table->decimal('remaining_deposit_credit', 15, 2)->default(0);
            $table->json('financial_snapshot')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'status']);
            $table->index(['new_room_id', 'status']);
            $table->index(['contract_id', 'effective_date']);
        });

        Schema::create('room_transfer_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('amenity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phase', 30);
            $table->string('name');
            $table->boolean('is_quantifiable')->default(true);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition', 30)->default('normal');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['room_transfer_id', 'phase']);
        });

        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->foreignId('room_id')->nullable()->after('contract_id')->constrained()->nullOnDelete();
        });
        DB::table('temporary_residences')->orderBy('id')->eachById(function (object $residence): void {
            DB::table('temporary_residences')->where('id', $residence->id)->update([
                'room_id' => DB::table('contracts')->where('id', $residence->contract_id)->value('room_id'),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('temporary_residences', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('room_id');
        });
        Schema::dropIfExists('room_transfer_items');
        Schema::dropIfExists('room_transfers');
    }
};
