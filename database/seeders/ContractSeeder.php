<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Tenant;
use App\Models\Room;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo tenants
        $tenants = [
            ['full_name' => 'Nguyễn Văn A', 'email' => 'tenant1@gmail.com', 'phone' => '0901234567', 'cccd' => '123456789'],
            ['full_name' => 'Trần Thị B', 'email' => 'tenant2@gmail.com', 'phone' => '0902345678', 'cccd' => '987654321'],
            ['full_name' => 'Hoàng Văn C', 'email' => 'tenant3@gmail.com', 'phone' => '0903456789', 'cccd' => '456789123'],
            ['full_name' => 'Lê Thị D', 'email' => 'tenant4@gmail.com', 'phone' => '0904567890', 'cccd' => '789123456'],
        ];

        foreach ($tenants as $tenant) {
            Tenant::updateOrCreate(
                ['cccd' => $tenant['cccd']],
                $tenant
            );
        }

        // Tạo contracts
        $rooms = Room::all();
        $tenantModels = Tenant::all();

        if ($rooms->count() > 0 && $tenantModels->count() > 0) {
            $contractData = [
                [
                    'room_id' => 1,
                    'tenant_id' => 1,
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-12-31',
                    'deposit_amount' => 5000000,
                    'status' => 'active'
                ],
                [
                    'room_id' => 2,
                    'tenant_id' => 2,
                    'start_date' => '2025-02-01',
                    'end_date' => '2026-01-31',
                    'deposit_amount' => 6000000,
                    'status' => 'active'
                ],
                [
                    'room_id' => 1,
                    'tenant_id' => 3,
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-12-31',
                    'deposit_amount' => 5000000,
                    'status' => 'expired'
                ],
            ];

            foreach ($contractData as $data) {
                Contract::updateOrCreate(
                    ['room_id' => $data['room_id'], 'tenant_id' => $data['tenant_id'], 'start_date' => $data['start_date']],
                    $data
                );
            }
        }
    }
}
