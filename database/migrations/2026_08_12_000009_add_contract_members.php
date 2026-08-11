<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_occupants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('replaces_occupant_id')->nullable()->constrained('contract_occupants')->nullOnDelete();
            $table->string('role', 30)->default('occupant');
            $table->string('full_name', 150);
            $table->date('date_of_birth')->nullable();
            $table->string('identity_number', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('relationship', 80)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('declared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->dateTime('actual_move_in_at')->nullable();
            $table->dateTime('actual_move_out_at')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'status']);
            $table->index(['identity_number', 'status']);
        });

        Schema::create('contract_occupant_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_occupant_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('action', 60);
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['contract_occupant_id', 'performed_at'], 'occupant_history_timeline_index');
        });

        $this->backfillRepresentatives();
    }

    private function backfillRepresentatives(): void
    {
        DB::table('contracts')
            ->join('tenants', 'tenants.id', '=', DB::raw('COALESCE(contracts.representative_tenant_id, contracts.tenant_id)'))
            ->select([
                'contracts.id as contract_id', 'contracts.status as contract_status',
                'contracts.actual_move_in_at', 'contracts.actual_move_out_at',
                'tenants.id as tenant_id', 'tenants.full_name', 'tenants.date_of_birth',
                'tenants.cccd', 'tenants.phone',
            ])
            ->orderBy('contracts.id')
            ->each(function (object $row): void {
                [$status, $moveInAt, $moveOutAt] = match ($row->contract_status) {
                    'active', 'expired' => ['checked_in', $row->actual_move_in_at, null],
                    'settling', 'completed' => ['moved_out', $row->actual_move_in_at, $row->actual_move_out_at],
                    'cancelled' => ['withdrawn', null, null],
                    default => ['approved', null, null],
                };

                $occupantId = DB::table('contract_occupants')->insertGetId([
                    'contract_id' => $row->contract_id,
                    'tenant_id' => $row->tenant_id,
                    'role' => 'representative',
                    'full_name' => $row->full_name,
                    'date_of_birth' => $row->date_of_birth,
                    'identity_number' => $row->cccd,
                    'phone' => $row->phone,
                    'relationship' => 'Người đại diện hợp đồng',
                    'status' => $status,
                    'reviewed_at' => now(),
                    'actual_move_in_at' => $moveInAt,
                    'actual_move_out_at' => $moveOutAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('contract_occupant_histories')->insert([
                    'contract_occupant_id' => $occupantId,
                    'from_status' => null,
                    'to_status' => $status,
                    'action' => 'migration_backfill',
                    'reason' => 'Khởi tạo người đại diện từ hợp đồng hiện có.',
                    'performed_at' => now(),
                    'metadata' => json_encode(['source' => 'contracts.tenant_id']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_occupant_histories');
        Schema::dropIfExists('contract_occupants');
    }
};
