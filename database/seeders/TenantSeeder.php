<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [

            [
                'full_name' => 'Nguyễn Văn A',
                'date_of_birth' => '2000-05-15',
                'gender' => 'male',
                'cccd' => '123456789012',
                'cccd_issue_date' => '2020-01-10',
                'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
                'phone' => '0901234567',
                'email' => 'nguyenvana@gmail.com',
                'address' => 'Hạ Long, Quảng Ninh',
            ],

            [
                'full_name' => 'Trần Thị B',
                'date_of_birth' => '2001-08-20',
                'gender' => 'female',
                'cccd' => '123456789013',
                'cccd_issue_date' => '2020-03-15',
                'cccd_issue_place' => 'Công an TP Hải Phòng',
                'phone' => '0902345678',
                'email' => 'tranthib@gmail.com',
                'address' => 'Lê Chân, Hải Phòng',
            ],

            [
                'full_name' => 'Hoàng Văn C',
                'date_of_birth' => '1999-12-01',
                'gender' => 'male',
                'cccd' => '123456789014',
                'cccd_issue_date' => '2019-05-20',
                'cccd_issue_place' => 'Công an tỉnh Hải Dương',
                'phone' => '0903456789',
                'email' => 'hoangvanc@gmail.com',
                'address' => 'Thanh Hà, Hải Dương',
            ],

            [
                'full_name' => 'Lê Thị D',
                'date_of_birth' => '2002-02-25',
                'gender' => 'female',
                'cccd' => '123456789015',
                'cccd_issue_date' => '2021-06-01',
                'cccd_issue_place' => 'Công an TP Hà Nội',
                'phone' => '0904567890',
                'email' => 'lethid@gmail.com',
                'address' => 'Hoàng Mai, Hà Nội',
            ],

        ];

        foreach ($tenants as $tenant) {

            Tenant::updateOrCreate(
                [
                    'cccd' => $tenant['cccd']
                ],
                $tenant
            );
        }
    }
}
