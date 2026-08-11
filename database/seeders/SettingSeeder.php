<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'electric_price' => 3500,
            'water_price' => 15000,
            'internet_fee' => 100000,
            'service_fee' => 50000,
            'parking_fee' => 75000,
            'property_name' => 'Nhà trọ StayMaster',
            'property_address' => 'Trịnh Văn Bô, Nam Từ Liêm, Hà Nội',
            'landlord_name' => 'Nguyễn Xuân Nam',
            'landlord_date_of_birth' => '2006-06-22',
            'landlord_identity_number' => '001206006081',
            'landlord_identity_issued_at' => '2019-09-21',
            'landlord_identity_issued_by' => 'Cục Cảnh Sát',
            'landlord_phone' => '0961152763',
            'landlord_address' => 'Trịnh Văn Bô, Nam Từ Liêm, Hà Nội',
            'bank_id' => 'MB',
            'bank_account_no' => '6666200066789',
            'bank_account_name' => 'NGUYEN XUAN NAM',
        ];

        Setting::currentOrCreate($defaults);
    }
}
