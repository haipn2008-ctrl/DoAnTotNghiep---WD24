<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_schedules', function (Blueprint $table): void {
            $table->id();
            $table->date('effective_from')->unique();
            $table->decimal('electric_price', 10, 2);
            $table->decimal('water_price', 10, 2);
            $table->decimal('internet_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->timestamps();
        });

        $setting = DB::table('settings')
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($setting) {
            DB::table('fee_schedules')->insert([
                'effective_from' => '2000-01-01',
                'electric_price' => $setting->electric_price,
                'water_price' => $setting->water_price,
                'internet_fee' => $setting->internet_fee,
                'service_fee' => $setting->service_fee,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('fee_schedule_id')
                ->nullable()
                ->after('utility_reading_id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('fee_schedule_id');
        });

        Schema::dropIfExists('fee_schedules');
    }
};
