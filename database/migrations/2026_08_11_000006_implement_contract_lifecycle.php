<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('property_name')->nullable();
            $table->text('property_address')->nullable();
            $table->string('landlord_name')->nullable();
            $table->date('landlord_date_of_birth')->nullable();
            $table->string('landlord_identity_number', 30)->nullable();
            $table->date('landlord_identity_issued_at')->nullable();
            $table->string('landlord_identity_issued_by')->nullable();
            $table->string('landlord_phone', 30)->nullable();
            $table->text('landlord_address')->nullable();
        });

        // Các enum cũ không thể chứa state machine mới. Chuyển sang VARCHAR để migration
        // chạy được trên cả MySQL/MariaDB và SQLite mà không xóa dữ liệu lịch sử.
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('status', 40)->default('draft')->change();
            $table->string('deposit_status', 40)->default('pending')->change();
            $table->dateTime('signed_at')->nullable()->change();

            $table->foreignId('signed_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('signature_due_at')->nullable()->index();
            $table->dateTime('deposit_due_at')->nullable()->index();
            $table->date('scheduled_move_in_date')->nullable()->index();
            $table->dateTime('reservation_expires_at')->nullable()->index();
            $table->dateTime('actual_move_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('actual_move_out_at')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('checkout_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deposit_resolution', 40)->nullable()->index();
            $table->dateTime('deposit_resolved_at')->nullable();
            $table->foreignId('deposit_resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('settlement_note')->nullable();

            // Snapshot để bản in lịch sử không đổi khi cấu hình tài sản/chủ nhà thay đổi.
            $table->string('landlord_name_snapshot')->nullable();
            $table->text('landlord_address_snapshot')->nullable();
            $table->string('landlord_phone_snapshot', 30)->nullable();
            $table->string('landlord_identity_snapshot', 30)->nullable();
            $table->text('property_address_snapshot')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 40)->default('unpaid')->change();
            $table->string('lifecycle_event_key', 100)->nullable()->unique();
            $table->dateTime('written_off_at')->nullable();
            $table->foreignId('written_off_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('write_off_reason')->nullable();
        });

        Schema::table('utility_readings', function (Blueprint $table) {
            $table->string('lifecycle_event_key', 100)->nullable()->unique();
        });

        Schema::create('contract_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('action', 60);
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['contract_id', 'performed_at']);
        });

        Schema::create('contract_lifecycle_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->string('dedupe_key', 120);
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->dateTime('detected_at');
            $table->dateTime('resolved_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['contract_id', 'type', 'dedupe_key'], 'contract_alert_dedupe_unique');
        });

        $this->backfillLifecycle();
        $this->backfillLifecycleKeys();
    }

    private function backfillLifecycle(): void
    {
        $setting = DB::table('settings')->where('is_active', true)->orderByDesc('id')->first()
            ?? DB::table('settings')->orderByDesc('id')->first();

        DB::table('contracts')->orderBy('id')->eachById(function (object $contract) use ($setting): void {
            $legacyStatus = $contract->status;
            $status = match ($legacyStatus) {
                'pending' => 'draft',
                'terminated' => $this->legacyTerminationStatus((int) $contract->id, (float) $contract->deposit_amount, $contract->deposit_status),
                default => $legacyStatus,
            };

            $wasOccupied = in_array($legacyStatus, ['active', 'expired'], true);
            $wasCheckedOut = $legacyStatus === 'terminated';
            $completed = $status === 'completed';
            $signedAt = $wasOccupied || $wasCheckedOut
                ? ($contract->signed_at ?: $contract->created_at)
                : null;
            $moveInAt = $wasOccupied || $wasCheckedOut
                ? ($contract->start_date.' 00:00:00')
                : null;
            $moveOutSource = $contract->actual_end_date ?: $contract->terminated_at ?: $contract->end_date;
            $moveOutAt = $wasCheckedOut
                ? date('Y-m-d H:i:s', strtotime((string) $moveOutSource))
                : null;

            DB::table('contracts')->where('id', $contract->id)->update([
                'status' => $status,
                'signed_at' => $signedAt,
                'scheduled_move_in_date' => $contract->start_date,
                'actual_move_in_at' => $moveInAt,
                'actual_move_out_at' => $moveOutAt,
                'checkout_reason' => $wasCheckedOut
                    ? ($contract->termination_note ?: $contract->termination_reason ?: 'Dữ liệu trả phòng chuyển đổi')
                    : null,
                'completed_at' => $completed ? $moveOutAt : null,
                'deposit_resolution' => $completed
                    ? ((float) $contract->deposit_amount > 0 ? 'refunded' : 'not_required')
                    : null,
                'deposit_resolved_at' => $completed ? $moveOutAt : null,
                'landlord_name_snapshot' => $setting?->landlord_name,
                'landlord_address_snapshot' => $setting?->landlord_address,
                'landlord_phone_snapshot' => $setting?->landlord_phone,
                'landlord_identity_snapshot' => $setting?->landlord_identity_number,
                'property_address_snapshot' => $setting?->property_address,
                'updated_at' => $contract->updated_at,
            ]);

            DB::table('contract_status_histories')->insert([
                'contract_id' => $contract->id,
                'from_status' => $legacyStatus,
                'to_status' => $status,
                'action' => 'legacy_migration',
                'reason' => 'Chuyển đổi dữ liệu hợp đồng cũ sang vòng đời có kiểm soát.',
                'performed_by' => null,
                'performed_at' => now(),
                'metadata' => json_encode([
                    'migrated' => true,
                    'legacy_status' => $legacyStatus,
                    'actual_move_in_source' => $moveInAt ? 'start_date' : null,
                    'requires_admin_review' => $status === 'settling',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    private function legacyTerminationStatus(int $contractId, float $depositAmount, ?string $depositStatus): string
    {
        $hasDebt = DB::table('invoices')
            ->where('contract_id', $contractId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->exists();
        $depositHandled = $depositAmount <= 0 || $depositStatus === 'returned';

        return ! $hasDebt && $depositHandled ? 'completed' : 'settling';
    }

    private function backfillLifecycleKeys(): void
    {
        foreach (['handover', 'checkout'] as $type) {
            DB::table('utility_readings')->where('reading_type', $type)
                ->whereNotNull('contract_id')->orderBy('id')->get()->groupBy('contract_id')
                ->each(function ($readings, $contractId) use ($type): void {
                    $first = $readings->first();
                    DB::table('utility_readings')->where('id', $first->id)->update([
                        'lifecycle_event_key' => "contract:{$contractId}:{$type}",
                    ]);
                });
        }

        DB::table('invoices')->where('invoice_type', 'deposit')->orderBy('id')->get()->groupBy('contract_id')
            ->each(function ($invoices, $contractId): void {
                DB::table('invoices')->where('id', $invoices->first()->id)->update([
                    'lifecycle_event_key' => "contract:{$contractId}:deposit",
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_lifecycle_alerts');
        Schema::dropIfExists('contract_status_histories');

        Schema::table('utility_readings', function (Blueprint $table) {
            $table->dropUnique(['lifecycle_event_key']);
            $table->dropColumn('lifecycle_event_key');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['lifecycle_event_key']);
            $table->dropConstrainedForeignId('written_off_by');
            $table->dropColumn(['lifecycle_event_key', 'written_off_at', 'write_off_reason']);
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['signature_due_at']);
            $table->dropIndex(['deposit_due_at']);
            $table->dropIndex(['scheduled_move_in_date']);
            $table->dropIndex(['reservation_expires_at']);
            $table->dropIndex(['deposit_resolution']);
            foreach (['signed_confirmed_by', 'checked_in_by', 'checked_out_by', 'cancelled_by', 'completed_by', 'deposit_resolved_by'] as $foreign) {
                $table->dropConstrainedForeignId($foreign);
            }
            $table->dropColumn([
                'signature_due_at', 'deposit_due_at', 'scheduled_move_in_date', 'reservation_expires_at',
                'actual_move_in_at', 'actual_move_out_at', 'checkout_reason', 'cancelled_at', 'cancel_reason',
                'completed_at', 'deposit_resolution', 'deposit_resolved_at', 'settlement_note',
                'landlord_name_snapshot', 'landlord_address_snapshot', 'landlord_phone_snapshot',
                'landlord_identity_snapshot', 'property_address_snapshot',
            ]);
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'property_name', 'property_address', 'landlord_name', 'landlord_date_of_birth',
                'landlord_identity_number', 'landlord_identity_issued_at', 'landlord_identity_issued_by',
                'landlord_phone', 'landlord_address',
            ]);
        });
    }
};
