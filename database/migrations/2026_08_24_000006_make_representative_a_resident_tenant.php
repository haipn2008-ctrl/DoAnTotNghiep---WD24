<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contract_occupants')
            ->join('contracts', 'contracts.id', '=', 'contract_occupants.contract_id')
            ->where('contract_occupants.role', 'representative')
            ->where('contract_occupants.status', 'non_resident')
            ->select([
                'contract_occupants.id',
                'contract_occupants.contract_id',
                'contracts.status as contract_status',
                'contracts.actual_move_in_at',
                'contracts.actual_move_out_at',
            ])
            ->orderBy('contract_occupants.id')
            ->each(function (object $row): void {
                [$status, $moveInAt, $moveOutAt] = match ($row->contract_status) {
                    'active', 'expired' => ['checked_in', $row->actual_move_in_at ?? now(), null],
                    'settling', 'completed' => ['moved_out', $row->actual_move_in_at, $row->actual_move_out_at ?? now()],
                    'cancelled' => ['withdrawn', null, null],
                    default => ['approved', null, null],
                };

                DB::table('contract_occupants')->where('id', $row->id)->update([
                    'status' => $status,
                    'actual_move_in_at' => $moveInAt,
                    'actual_move_out_at' => $moveOutAt,
                    'updated_at' => now(),
                ]);

                DB::table('contract_occupant_histories')->insert([
                    'contract_occupant_id' => $row->id,
                    'from_status' => 'non_resident',
                    'to_status' => $status,
                    'action' => 'representative_became_resident_tenant',
                    'reason' => 'Chuyển sang mô hình mọi thành viên hợp đồng đều là người thuê trực tiếp.',
                    'performed_at' => now(),
                    'metadata' => json_encode(['migration' => '2026_08_24_000006']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        DB::table('contracts')->select('id')->orderBy('id')->each(function (object $contract): void {
            $count = DB::table('contract_occupants')
                ->where('contract_id', $contract->id)
                ->whereIn('status', ['pending', 'approved', 'checked_in'])
                ->count();
            DB::table('contracts')->where('id', $contract->id)->update(['number_of_people' => $count]);
        });

        DB::table('rooms')->select('id')->orderBy('id')->each(function (object $room): void {
            $count = DB::table('contract_occupants')
                ->join('contracts', 'contracts.id', '=', 'contract_occupants.contract_id')
                ->where('contracts.room_id', $room->id)
                ->whereIn('contracts.status', ['active', 'expired'])
                ->where('contract_occupants.status', 'checked_in')
                ->count();
            DB::table('rooms')->where('id', $room->id)->update(['current_people' => $count]);
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn('representative_is_occupant');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->boolean('representative_is_occupant')->default(true)->after('representative_tenant_id');
        });
    }
};
