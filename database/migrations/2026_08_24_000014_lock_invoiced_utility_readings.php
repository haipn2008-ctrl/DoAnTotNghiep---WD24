<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE utility_readings MODIFY status ENUM('draft', 'confirmed', 'locked') NOT NULL DEFAULT 'draft'");
        }

        DB::table('utility_readings')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('invoices')
                    ->whereColumn('invoices.utility_reading_id', 'utility_readings.id');
            })
            ->update(['status' => 'locked']);
    }

    public function down(): void
    {
        DB::table('utility_readings')
            ->where('status', 'locked')
            ->update(['status' => 'confirmed']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE utility_readings MODIFY status ENUM('draft', 'confirmed') NOT NULL DEFAULT 'draft'");
        }
    }
};
