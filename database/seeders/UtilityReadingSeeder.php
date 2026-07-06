<?php

namespace Database\Seeders;

use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UtilityReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $utilityData = [

            /*
            |--------------------------------------------------------------------------
            | Hợp đồng 1 - Phòng P101
            |--------------------------------------------------------------------------
            */

            [
                'contract_id'          => 1,
                'room_id'              => 1,

                'month'                => 5,
                'year'                 => 2026,
                'record_date'          => '2026-05-31',

                'electric_old'         => 0,
                'electric_new'         => 1200,
                'electric_unit_price'  => 3500,
                'electricity_image'    => null,

                'water_old'            => 0,
                'water_new'            => 150,
                'water_unit_price'     => 18000,
                'water_image'          => null,

                'status'               => 'confirmed',
                'recorded_by'          => 1,
                'note'                 => null,

                'created_at'           => $now,
                'updated_at'           => $now,
            ],

            [
                'contract_id'          => 1,
                'room_id'              => 1,

                'month'                => 6,
                'year'                 => 2026,
                'record_date'          => '2026-06-30',

                'electric_old'         => 1200,
                'electric_new'         => 1350,
                'electric_unit_price'  => 3500,
                'electricity_image'    => null,

                'water_old'            => 150,
                'water_new'            => 162,
                'water_unit_price'     => 18000,
                'water_image'          => null,

                'status'               => 'confirmed',
                'recorded_by'          => 1,
                'note'                 => null,

                'created_at'           => $now,
                'updated_at'           => $now,
            ],

            /*
            |--------------------------------------------------------------------------
            | Hợp đồng 2 - Phòng P102
            |--------------------------------------------------------------------------
            */

            [
                'contract_id'          => 2,
                'room_id'              => 2,

                'month'                => 3,
                'year'                 => 2026,
                'record_date'          => '2026-03-31',

                'electric_old'         => 0,
                'electric_new'         => 3000,
                'electric_unit_price'  => 3500,
                'electricity_image'    => null,

                'water_old'            => 0,
                'water_new'            => 400,
                'water_unit_price'     => 18000,
                'water_image'          => null,

                'status'               => 'confirmed',
                'recorded_by'          => 1,
                'note'                 => null,

                'created_at'           => $now,
                'updated_at'           => $now,
            ],

            [
                'contract_id'          => 2,
                'room_id'              => 2,

                'month'                => 4,
                'year'                 => 2026,
                'record_date'          => '2026-04-30',

                'electric_old'         => 3000,
                'electric_new'         => 3180,
                'electric_unit_price'  => 3500,
                'electricity_image'    => null,

                'water_old'            => 400,
                'water_new'            => 415,
                'water_unit_price'     => 18000,
                'water_image'          => null,

                'status'               => 'confirmed',
                'recorded_by'          => 1,
                'note'                 => null,

                'created_at'           => $now,
                'updated_at'           => $now,
            ],

            [
                'contract_id'          => 2,
                'room_id'              => 2,

                'month'                => 5,
                'year'                 => 2026,
                'record_date'          => '2026-05-31',

                'electric_old'         => 3180,
                'electric_new'         => 3400,
                'electric_unit_price'  => 3500,
                'electricity_image'    => null,

                'water_old'            => 415,
                'water_new'            => 432,
                'water_unit_price'     => 18000,
                'water_image'          => null,

                'status'               => 'confirmed',
                'recorded_by'          => 1,
                'note'                 => null,

                'created_at'           => $now,
                'updated_at'           => $now,
            ],

            [
                'contract_id'          => 2,
                'room_id'              => 2,

                'month'                => 6,
                'year'                 => 2026,
                'record_date'          => '2026-06-30',

                'electric_old'         => 3400,
                'electric_new'         => 3650,
                'electric_unit_price'  => 3500,
                'electricity_image'    => null,

                'water_old'            => 432,
                'water_new'            => 450,
                'water_unit_price'     => 18000,
                'water_image'          => null,

                'status'               => 'confirmed',
                'recorded_by'          => 1,
                'note'                 => null,

                'created_at'           => $now,
                'updated_at'           => $now,
            ],

        ];

        UtilityReading::insert($utilityData);
    }
}
