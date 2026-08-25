<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

class VehicleCapacityService
{
    /**
     * Kiểm tra và giữ chỗ xe khi người thuê gửi yêu cầu.
     * Phương thức này phải được gọi bên trong transaction.
     */
    public function ensureCanSubmit(Tenant $owner, ?Vehicle $except = null): void
    {
        Tenant::query()->lockForUpdate()->findOrFail($owner->id);

        $existing = Vehicle::query()
            ->where('tenant_id', $owner->id)
            ->whereIn('status', [Vehicle::STATUS_PENDING, Vehicle::STATUS_APPROVED])
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->lockForUpdate()
            ->get();

        if ($existing->isNotEmpty()) {
            $this->fail('owner_tenant_id', 'Mỗi người đang ở chỉ được đăng ký một xe.');
        }

        $contract = $this->currentContract($owner);
        if (! $contract) {
            $this->fail('vehicle', 'Chỉ người đang ở trong một hợp đồng hoạt động mới được đăng ký phương tiện.');
        }

        $this->lockContract($contract);
        $occupants = $this->checkedInMembers($contract);
        $reservedVehicles = Vehicle::query()
            ->whereIn('tenant_id', $occupants->pluck('tenant_id')->filter()->unique())
            ->whereIn('status', [Vehicle::STATUS_PENDING, Vehicle::STATUS_APPROVED])
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->lockForUpdate()
            ->get()
            ->count();

        if ($reservedVehicles >= $occupants->count()) {
            $this->fail('vehicle', 'Phòng đã đủ số xe theo số người đang ở.');
        }
    }

    /**
     * Kiểm tra lần cuối khi quản trị viên duyệt để tránh vượt giới hạn.
     * Phương thức này phải được gọi bên trong transaction.
     */
    public function ensureCanApprove(Vehicle $vehicle): void
    {
        $otherApproved = Vehicle::query()
            ->where('tenant_id', $vehicle->tenant_id)
            ->where('status', Vehicle::STATUS_APPROVED)
            ->whereKeyNot($vehicle->id)
            ->lockForUpdate()
            ->get();

        if ($otherApproved->isNotEmpty()) {
            $this->fail('status', 'Chủ xe này đã có một xe được duyệt.');
        }

        $contract = $this->currentContract($vehicle->tenant);
        if (! $contract) {
            return;
        }

        $this->lockContract($contract);
        $occupants = $this->checkedInMembers($contract);
        $approvedVehicles = Vehicle::query()
            ->whereIn('tenant_id', $occupants->pluck('tenant_id')->filter()->unique())
            ->where('status', Vehicle::STATUS_APPROVED)
            ->whereKeyNot($vehicle->id)
            ->lockForUpdate()
            ->get()
            ->count();

        if ($approvedVehicles >= $occupants->count()) {
            $this->fail('status', 'Không thể duyệt vì phòng đã đủ số xe theo số người đang ở.');
        }
    }

    public function currentContract(Tenant $tenant): ?Contract
    {
        return Contract::query()
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->whereHas('members', fn ($query) => $query
                ->where('tenant_id', $tenant->id)
                ->where('status', ContractTenant::STATUS_CHECKED_IN))
            ->latest('id')
            ->first();
    }

    private function checkedInMembers(Contract $contract)
    {
        return ContractTenant::query()
            ->where('contract_id', $contract->id)
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->lockForUpdate()
            ->get();
    }

    private function lockContract(Contract $contract): void
    {
        Contract::query()->lockForUpdate()->findOrFail($contract->id);
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
