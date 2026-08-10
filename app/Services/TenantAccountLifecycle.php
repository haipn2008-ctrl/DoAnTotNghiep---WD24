<?php

namespace App\Services;

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
            ->whereIn('status', ['pending', 'active'])
            ->exists();

        if ($hasOpenContract) {
            $user->update(['status' => User::STATUS_ACTIVE]);

            return User::STATUS_ACTIVE;
        }

        $hasOutstandingInvoice = Invoice::query()
            ->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->exists();

        $status = $hasOutstandingInvoice ? User::STATUS_SETTLING : User::STATUS_INACTIVE;
        $user->update(['status' => $status]);

        return $status;
    }
}
