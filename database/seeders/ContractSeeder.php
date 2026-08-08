<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = [
            [
                'contract_code' => 'HD001',
                'room_code' => 'P101',
                'tenant_cccd' => '123456789012',
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
                'room_code' => 'P102',
                'tenant_cccd' => '123456789013',
                'monthly_rent' => 4500000,
                'deposit_amount' => 6000000,
                'number_of_people' => 3,
                'signed_at' => '2025-01-25',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'status' => 'active',
            ],
        ];

        foreach ($contracts as $data) {
            $roomId = Room::where('room_code', $data['room_code'])->value('id');
            $tenantId = Tenant::where('cccd', $data['tenant_cccd'])->value('id');

            if (! $roomId || ! $tenantId) {
                $this->command?->warn("Bỏ qua {$data['contract_code']}: thiếu phòng hoặc khách thuê.");

                continue;
            }

            Contract::updateOrCreate(
                ['contract_code' => $data['contract_code']],
                [
                    'room_id' => $roomId,
                    'tenant_id' => $tenantId,
                    'monthly_rent' => $data['monthly_rent'],
                    'deposit_amount' => $data['deposit_amount'],
                    'number_of_people' => $data['number_of_people'],
                    'signed_at' => $data['signed_at'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'status' => $data['status'],
                ]
            );
        }
    }
}
