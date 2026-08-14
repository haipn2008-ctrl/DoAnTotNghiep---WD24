<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('motorcycle_parking_fee', 10, 2)->default(0)->after('parking_fee');
            $table->decimal('car_parking_fee', 10, 2)->default(0)->after('motorcycle_parking_fee');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->string('parking_vehicle_type', 20)->nullable()->after('service_enabled');
        });

        DB::table('settings')->update([
            'motorcycle_parking_fee' => DB::raw('parking_fee'),
        ]);
        DB::table('contracts')->where('parking_quantity', '>', 0)->update([
            'parking_vehicle_type' => 'motorcycle',
        ]);
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('parking_vehicle_type');
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['motorcycle_parking_fee', 'car_parking_fee']);
        });
    }
};
