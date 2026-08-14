<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AvailableTenantSeeder::class,
            AmenitySeeder::class,
            SettingSeeder::class,
            DemoPropertySeeder::class,
            AuthenticationScenarioSeeder::class,
        ]);

        DB::table('tenants')->whereNull('payment_code')->orderBy('id')->eachById(function ($tenant) {
            DB::table('tenants')->where('id', $tenant->id)->update([
                'payment_code' => 'KH'.str_pad((string) $tenant->id, 8, '0', STR_PAD_LEFT),
            ]);
        });
    }
}
