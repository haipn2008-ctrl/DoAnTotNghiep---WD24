<?php

namespace App\Services;

use App\Models\ContractLifecycleAlert;
use App\Models\Vehicle;
use Illuminate\Support\Str;

class AdminNotificationService
{
    public function vehicleSubmitted(Vehicle $vehicle, bool $isChange = false): ContractLifecycleAlert
    {
        $this->resolveVehicleReviews($vehicle);
        $vehicle->loadMissing('tenant');
        $tenantName = $vehicle->tenant?->full_name ?: 'Khách thuê';
        $vehicleName = $vehicle->vehicle_name ?: $this->vehicleTypeLabel($vehicle->vehicle_type);

        return ContractLifecycleAlert::query()->create([
            'contract_id' => null,
            'tenant_id' => $vehicle->tenant_id,
            'vehicle_id' => $vehicle->id,
            'type' => 'vehicle_review',
            'dedupe_key' => 'vehicle-review:'.Str::uuid(),
            'title' => $isChange ? 'Khách đổi phương tiện, cần duyệt lại' : 'Phương tiện mới chờ duyệt',
            'message' => "{$tenantName} đã ".($isChange ? 'thay đổi' : 'đăng ký')." {$vehicleName}. Vui lòng kiểm tra thông tin và ảnh xe.",
            'metadata' => ['event' => $isChange ? 'changed' : 'submitted'],
            'detected_at' => now(),
        ]);
    }

    public function vehicleReviewed(Vehicle $vehicle): void
    {
        $this->resolveVehicleReviews($vehicle);
    }

    public function vehicleRequestCancelled(Vehicle $vehicle): void
    {
        $this->resolveVehicleReviews($vehicle);
    }

    public function vehicleRemoved(Vehicle $vehicle): ContractLifecycleAlert
    {
        $this->resolveVehicleReviews($vehicle);
        $vehicle->loadMissing('tenant');
        $tenantName = $vehicle->tenant?->full_name ?: 'Khách thuê';
        $vehicleName = $vehicle->vehicle_name ?: $this->vehicleTypeLabel($vehicle->vehicle_type);
        $identifier = $vehicle->license_plate ? " ({$vehicle->license_plate})" : '';

        return ContractLifecycleAlert::query()->create([
            'contract_id' => null,
            'tenant_id' => $vehicle->tenant_id,
            'vehicle_id' => $vehicle->id,
            'type' => 'vehicle_removed',
            'dedupe_key' => 'vehicle-removed:'.Str::uuid(),
            'title' => 'Khách đã gỡ phương tiện',
            'message' => "{$tenantName} đã gỡ {$vehicleName}{$identifier} khỏi danh sách phương tiện.",
            'metadata' => ['event' => 'removed'],
            'detected_at' => now(),
        ]);
    }

    private function resolveVehicleReviews(Vehicle $vehicle): void
    {
        ContractLifecycleAlert::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('type', 'vehicle_review')
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);
    }

    private function vehicleTypeLabel(string $type): string
    {
        return match ($type) {
            'motorcycle' => 'xe máy',
            'electric_motorcycle' => 'xe máy điện',
            'bicycle' => 'xe đạp',
            default => 'phương tiện',
        };
    }
}
