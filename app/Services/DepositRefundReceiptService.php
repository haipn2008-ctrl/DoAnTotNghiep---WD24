<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DepositRefundReceiptService
{
    public const SOURCE_TENANT = 'tenant';

    public const SOURCE_AUTOMATIC = 'automatic';

    public function confirm(Contract $contract, string $source, ?User $actor = null): Contract
    {
        if (! in_array($source, [self::SOURCE_TENANT, self::SOURCE_AUTOMATIC], true)) {
            throw new InvalidArgumentException('Nguồn xác nhận nhận tiền hoàn cọc không hợp lệ.');
        }

        return DB::transaction(function () use ($contract, $source, $actor): Contract {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);

            if ($contract->isRefundCompleted()) {
                return $contract;
            }

            if (! $contract->isAwaitingRefundReceiptConfirmation()) {
                throw ValidationException::withMessages([
                    'refund' => 'Khoản hoàn cọc này chưa ở trạng thái chờ khách xác nhận nhận tiền.',
                ]);
            }

            if ($source === self::SOURCE_AUTOMATIC
                && (! $contract->deposit_receipt_confirmation_due_at
                    || $contract->deposit_receipt_confirmation_due_at->isFuture())) {
                throw ValidationException::withMessages([
                    'refund' => 'Khoản hoàn cọc này chưa hết thời hạn xác nhận 24 giờ.',
                ]);
            }

            $finalDepositStatus = (float) $contract->deposit_deduction_amount > 0
                ? Contract::DEPOSIT_PARTIAL
                : Contract::DEPOSIT_RETURNED;
            $resolution = $finalDepositStatus === Contract::DEPOSIT_PARTIAL
                ? Contract::DEPOSIT_DEDUCTED
                : Contract::DEPOSIT_REFUNDED;
            $description = $source === self::SOURCE_AUTOMATIC
                ? 'Hệ thống tự động xác nhận khách đã nhận đủ tiền sau 24 giờ không có phản hồi.'
                : 'Khách thuê xác nhận đã nhận đủ tiền hoàn cọc.';

            $contract->forceFill([
                'deposit_status' => $finalDepositStatus,
                'deposit_resolution' => $resolution,
                'deposit_receipt_confirmed_at' => now(),
                'deposit_receipt_confirmation_source' => $source,
                'deposit_resolved_at' => now(),
                'deposit_resolved_by' => $actor?->id,
            ])->save();

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::DEPOSIT_PROCESSED,
                $description,
                null,
                ['deposit_status' => Contract::DEPOSIT_REFUND_PROCESSING],
                [
                    'deposit_status' => $finalDepositStatus,
                    'deposit_resolution' => $resolution,
                    'receipt_confirmation_source' => $source,
                ],
                $actor?->id,
            );

            return $contract->fresh();
        }, 3);
    }
}
