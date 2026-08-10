<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::currentOrCreate([
            'electric_price' => 3500,
            'water_price' => 15000,
            'internet_fee' => 100000,
            'service_fee' => 50000,
        ]);
    }
}
