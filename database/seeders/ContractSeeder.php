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
                    'contract_code' => 'HD001',
                    'room_id' => 1,
                    'tenant_id' => 1,

                    'monthly_rent' => 3500000,
                    'deposit_amount' => 5000000,
                    'number_of_people' => 2,

                    'signed_at' => '2025-12-25',
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-12-31',

                    'status' => 'active',
                ],

                [
                    'contract_code' => 'HD002',
                    'room_id' => 2,
                    'tenant_id' => 2,

                    'monthly_rent' => 4500000,
                    'deposit_amount' => 6000000,
                    'number_of_people' => 3,

                    'signed_at' => '2025-01-25',
                    'start_date' => '2026-04-01',
                    'end_date' => '2027-03-31',

                    'status' => 'active',
                ],

            ];

            foreach ($contractData as $data) {
                Contract::updateOrCreate(
                    [
                        'contract_code' => $data['contract_code']
                    ],
                    $data
                );
            }
        }
    }
}
