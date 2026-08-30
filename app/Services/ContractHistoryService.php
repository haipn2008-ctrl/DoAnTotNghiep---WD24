<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractHistory;
use Illuminate\Support\Facades\Auth;

class ContractHistoryService
{
    /*
    |--------------------------------------------------------------------------
    | Các hành động của hợp đồng
    |--------------------------------------------------------------------------
    */

    public const CREATED               = 'created';
    public const UPDATED               = 'updated';

    public const SENT_FOR_SIGNATURE    = 'sent_for_signature';
    public const RECALLED              = 'recalled';
    public const SIGNED                = 'signed';

    public const DEPOSIT_PAID          = 'deposit_paid';

    public const ACTIVATED             = 'activated';

    public const EXTENSION_REQUESTED   = 'extension_requested';
    public const EXTENDED              = 'extended';
    public const EXTENSION_REJECTED    = 'extension_rejected';

    public const TERMINATION_REQUESTED = 'termination_requested';
    public const TERMINATION_APPROVED  = 'termination_approved';
    public const TERMINATION_REJECTED  = 'termination_rejected';
    public const TERMINATED            = 'terminated';

    // Xử lý cọc sau khi hợp đồng kết thúc:
    // hoàn toàn bộ / hoàn một phần / không hoàn
    public const DEPOSIT_PROCESSED     = 'deposit_processed';


    /*
    |--------------------------------------------------------------------------
    | Ghi lịch sử chung
    |--------------------------------------------------------------------------
    */

    public static function log(
        Contract $contract,
        string $action,
        ?string $description = null,
        ?string $reason = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?int $userId = null
    ): ContractHistory {
        return ContractHistory::create([
            'contract_id' => $contract->id,

            // Người thực hiện thao tác: Admin hoặc Client đang đăng nhập
            'user_id' => $userId ?? Auth::id(),

            'action' => $action,
            'reason' => $reason,
            'description' => $description,

            'old_data' => $oldData,
            'new_data' => $newData,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Tạo hợp đồng
    |--------------------------------------------------------------------------
    */

    public static function created(Contract $contract): ContractHistory
    {
        return self::log(
            $contract,
            self::CREATED,
            'Hợp đồng đã được tạo.',
            null,
            null,
            self::snapshot($contract)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Chỉnh sửa hợp đồng
    |--------------------------------------------------------------------------
    */

    public static function updated(
        Contract $contract,
        array $oldData,
        array $newData,
        ?string $reason = null
    ): ?ContractHistory {

        $changes = self::getChanges($oldData, $newData);

        // Không có gì thay đổi thì không ghi lịch sử
        if (empty($changes['old']) && empty($changes['new'])) {
            return null;
        }

        return self::log(
            $contract,
            self::UPDATED,
            'Thông tin hợp đồng đã được chỉnh sửa.',
            $reason,
            $changes['old'],
            $changes['new']
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Lấy snapshot dữ liệu quan trọng của hợp đồng
    |--------------------------------------------------------------------------
    */

    public static function snapshot(Contract $contract): array
    {
        return [
            'room_id'        => $contract->room_id,
            'tenant_id'      => $contract->tenant_id,

            'start_date' => $contract->start_date?->format('Y-m-d'),
            'end_date'   => $contract->end_date?->format('Y-m-d'),

            'monthly_rent'   => $contract->monthly_rent,
            'deposit_amount' => $contract->deposit_amount,
            
            'status'         => $contract->status,
            'deposit_status' => $contract->deposit_status,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | So sánh dữ liệu trước và sau
    |--------------------------------------------------------------------------
    */

    public static function getChanges(array $oldData, array $newData): array
    {
        $oldChanges = [];
        $newChanges = [];

        foreach ($newData as $key => $newValue) {

            $oldValue = $oldData[$key] ?? null;

            // Chuẩn hóa để tránh 3500000 và "3500000" bị coi là khác
            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            $oldChanges[$key] = $oldValue;
            $newChanges[$key] = $newValue;
        }

        return [
            'old' => $oldChanges,
            'new' => $newChanges,
        ];
    }
}
