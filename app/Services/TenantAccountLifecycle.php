<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;

class TenantAccountLifecycle
{
    public function sync(Tenant $tenant): ?string
    {
        $user = $tenant->user;

        if (! $user || in_array($user->status, [User::STATUS_LOCKED, User::STATUS_PENDING], true)) {
            return $user?->status;
        }

        $hasOpenContract = $tenant->contracts()
            ->whereIn('status', [
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_PENDING_DEPOSIT,
                Contract::STATUS_AWAITING_MOVE_IN,
                Contract::STATUS_ACTIVE,
                Contract::STATUS_EXPIRED,
            ])
            ->exists();

        if ($hasOpenContract) {
            $user->update(['status' => User::STATUS_ACTIVE]);

            return User::STATUS_ACTIVE;
        }

        $hasOutstandingInvoice = Invoice::query()
            ->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->exists();

        $hasSettlement = $tenant->contracts()->where('status', Contract::STATUS_SETTLING)->exists();
        $hasRentalHistory = $tenant->contracts()
            ->where(function ($query): void {
                $query->whereNotNull('actual_move_in_at')
                    ->orWhereIn('status', [Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED]);
            })
            ->exists();
        $status = $hasOutstandingInvoice || $hasSettlement
            ? User::STATUS_SETTLING
            : ($hasRentalHistory ? User::STATUS_FORMER : User::STATUS_ACTIVE);
        $user->update(['status' => $status]);

        return $status;
    }
}
