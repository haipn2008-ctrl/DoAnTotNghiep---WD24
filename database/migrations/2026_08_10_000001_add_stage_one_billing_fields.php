<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contracts', 'internet_enabled')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->boolean('internet_enabled')->default(false)->after('number_of_people');
                $table->boolean('service_enabled')->default(false)->after('internet_enabled');
                $table->unsignedInteger('parking_quantity')->default(0)->after('service_enabled');
            });
        }

        if (! Schema::hasColumn('utility_readings', 'contract_id')) {
            // MySQL cần một index riêng cho khóa ngoại room_id trước khi bỏ unique index cũ.
            Schema::table('utility_readings', function (Blueprint $table) {
                $table->index('room_id', 'utility_readings_room_id_index');
            });
            Schema::table('utility_readings', function (Blueprint $table) {
                $table->dropUnique(['room_id', 'month', 'year']);
                $table->foreignId('contract_id')->nullable()->after('room_id')->constrained()->nullOnDelete();
                $table->string('reading_type', 20)->default('periodic')->after('year')->index();
                $table->index(['room_id', 'record_date']);
            });
        }

        if (! Schema::hasColumn('settings', 'bank_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('bank_id', 30)->nullable()->after('payment_due_days');
                $table->string('bank_account_no', 30)->nullable()->after('bank_id');
                $table->string('bank_account_name', 100)->nullable()->after('bank_account_no');
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER utility_readings_stage_one_insert BEFORE INSERT ON utility_readings
                WHEN NOT (NEW.month BETWEEN 1 AND 12) OR NOT (NEW.year BETWEEN 2000 AND 2100)
                    OR NOT (NEW.electricity_old >= 0 AND NEW.electricity_new >= NEW.electricity_old)
                    OR NOT (NEW.water_old >= 0 AND NEW.water_new >= NEW.water_old)
                BEGIN SELECT RAISE(ABORT, 'utility_readings integrity constraint failed'); END");
            DB::unprepared("CREATE TRIGGER utility_readings_stage_one_update BEFORE UPDATE ON utility_readings
                WHEN NOT (NEW.month BETWEEN 1 AND 12) OR NOT (NEW.year BETWEEN 2000 AND 2100)
                    OR NOT (NEW.electricity_old >= 0 AND NEW.electricity_new >= NEW.electricity_old)
                    OR NOT (NEW.water_old >= 0 AND NEW.water_new >= NEW.water_old)
                BEGIN SELECT RAISE(ABORT, 'utility_readings integrity constraint failed'); END");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS utility_readings_stage_one_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS utility_readings_stage_one_update');
        }
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['bank_id', 'bank_account_no', 'bank_account_name']);
        });

        Schema::table('utility_readings', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'record_date']);
            $table->dropConstrainedForeignId('contract_id');
            $table->dropColumn('reading_type');
            $table->unique(['room_id', 'month', 'year']);
            $table->dropIndex('utility_readings_room_id_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['internet_enabled', 'service_enabled', 'parking_quantity']);
        });
    }
};
