<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $completed = DB::table('room_transfers')
            ->where('status', 'completed')
            ->whereNotNull('effective_date')
            ->get(['contract_id', 'effective_date']);

        foreach ($completed as $item) {
            $monthStart = date('Y-m-01 00:00:00', strtotime($item->effective_date));
            $monthEnd = date('Y-m-t 23:59:59', strtotime($item->effective_date));
            $duplicateIds = DB::table('room_transfers')
                ->where('contract_id', $item->contract_id)
                ->whereIn('status', ['pending', 'pending_appendix'])
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->pluck('id');

            if ($duplicateIds->isEmpty()) {
                continue;
            }

            DB::table('room_transfers')->whereIn('id', $duplicateIds)->update([
                'status' => 'rejected',
                'admin_reason' => 'Hệ thống hủy yêu cầu trùng vì hợp đồng đã đổi phòng trong cùng tháng.',
                'updated_at' => now(),
            ]);
            DB::table('contract_appendices')->whereIn('room_transfer_id', $duplicateIds)
                ->whereIn('status', ['draft', 'pending_tenant', 'pending_signature'])
                ->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'rejection_reason' => 'Yêu cầu đổi phòng trùng trong cùng tháng đã bị hệ thống hủy.',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Dữ liệu nghiệp vụ đã bị từ chối không được tự động mở lại khi rollback.
    }
};
