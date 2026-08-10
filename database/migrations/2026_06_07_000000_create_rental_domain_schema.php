<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code')->unique();
            $table->integer('floor');
            $table->decimal('price', 12, 2);
            $table->decimal('area', 8, 2);
            $table->unsignedInteger('max_people')->default(4);
            $table->unsignedInteger('current_people')->default(0);
            $table->string('thumbnail')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available')->index();
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('cccd')->unique();
            $table->date('cccd_issue_date')->nullable();
            $table->string('cccd_issue_place')->nullable();
            $table->string('phone')->unique();
            $table->string('email')->nullable()->unique();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('amenity_room', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['room_id', 'amenity_id']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_code')->unique();
            $table->foreignId('room_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('representative_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->decimal('monthly_rent', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->enum('deposit_status', ['pending', 'paid', 'returned'])->default('pending');
            $table->timestamp('deposit_paid_at')->nullable();
            $table->unsignedInteger('number_of_people')->default(1);
            $table->date('signed_at')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamp('extended_at')->nullable();
            $table->date('extend_start_date')->nullable();
            $table->date('extend_end_date')->nullable();
            $table->string('extend_reason')->nullable();
            $table->text('extend_note')->nullable();
            $table->date('terminated_at')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->text('termination_note')->nullable();
            $table->enum('terminated_by', ['admin', 'tenant'])->nullable();
            $table->string('contract_file')->nullable();
            $table->enum('status', ['pending', 'active', 'expired', 'terminated'])->default('pending')->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('utility_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->date('record_date')->nullable();
            $table->unsignedInteger('electricity_old')->default(0);
            $table->unsignedInteger('electricity_new');
            $table->unsignedInteger('water_old')->default(0);
            $table->unsignedInteger('water_new');
            $table->string('electricity_image')->nullable();
            $table->string('water_image')->nullable();
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['room_id', 'month', 'year']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('utility_reading_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_code')->nullable()->unique();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('room_fee', 12, 2);
            $table->decimal('electricity_fee', 12, 2)->default(0);
            $table->decimal('water_fee', 12, 2)->default(0);
            $table->decimal('internet_fee', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid')->index();
            $table->timestamps();
            $table->unique(['room_id', 'month', 'year']);
        });

        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedInteger('old_index')->nullable();
            $table->unsignedInteger('new_index')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_paid', 12, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'qr'])->default('cash');
            $table->string('transaction_code')->nullable()->unique();
            $table->enum('status', ['pending', 'success', 'failed'])->default('success')->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('proof_image')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('electric_price', 10, 2);
            $table->decimal('water_price', 10, 2);
            $table->decimal('internet_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('parking_fee', 10, 2)->default(0);
            $table->unsignedTinyInteger('invoice_day')->default(5);
            $table->unsignedTinyInteger('payment_due_days')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('submission_token')->nullable()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('category', ['repair', 'invoice', 'utility', 'contract', 'other']);
            $table->string('subject');
            $table->text('description');
            $table->string('attachment')->nullable();
            $table->enum('status', ['new', 'in_progress', 'resolved', 'rejected'])->default('new')->index();
            $table->text('admin_response')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        $this->addDatabaseChecks();
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_details');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('utility_readings');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('amenity_room');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('rooms');
    }

    private function addDatabaseChecks(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER utility_readings_integrity_insert BEFORE INSERT ON utility_readings
                WHEN NOT (NEW.month BETWEEN 1 AND 12) OR NOT (NEW.year BETWEEN 2000 AND 2100)
                    OR NOT (NEW.electricity_old >= 0 AND NEW.electricity_new >= NEW.electricity_old)
                    OR NOT (NEW.water_old >= 0 AND NEW.water_new >= NEW.water_old)
                BEGIN SELECT RAISE(ABORT, 'utility_readings integrity constraint failed'); END");
            DB::unprepared("CREATE TRIGGER utility_readings_integrity_update BEFORE UPDATE ON utility_readings
                WHEN NOT (NEW.month BETWEEN 1 AND 12) OR NOT (NEW.year BETWEEN 2000 AND 2100)
                    OR NOT (NEW.electricity_old >= 0 AND NEW.electricity_new >= NEW.electricity_old)
                    OR NOT (NEW.water_old >= 0 AND NEW.water_new >= NEW.water_old)
                BEGIN SELECT RAISE(ABORT, 'utility_readings integrity constraint failed'); END");
            DB::unprepared("CREATE TRIGGER payments_amount_positive_insert BEFORE INSERT ON payments
                WHEN NOT (NEW.amount_paid > 0) BEGIN SELECT RAISE(ABORT, 'payment amount must be positive'); END");
            DB::unprepared("CREATE TRIGGER payments_amount_positive_update BEFORE UPDATE OF amount_paid ON payments
                WHEN NOT (NEW.amount_paid > 0) BEGIN SELECT RAISE(ABORT, 'payment amount must be positive'); END");
            DB::statement('CREATE UNIQUE INDEX settings_single_active_unique ON settings (is_active) WHERE is_active = 1');

            return;
        }

        DB::statement('ALTER TABLE utility_readings ADD CONSTRAINT utility_readings_month_check CHECK (month BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE utility_readings ADD CONSTRAINT utility_readings_year_check CHECK (year BETWEEN 2000 AND 2100)');
        DB::statement('ALTER TABLE utility_readings ADD CONSTRAINT utility_readings_electricity_check CHECK (electricity_old >= 0 AND electricity_new >= electricity_old)');
        DB::statement('ALTER TABLE utility_readings ADD CONSTRAINT utility_readings_water_check CHECK (water_old >= 0 AND water_new >= water_old)');
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_positive_check CHECK (amount_paid > 0)');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE settings ADD active_setting_guard TINYINT GENERATED ALWAYS AS (CASE WHEN is_active = 1 THEN 1 ELSE NULL END) STORED');
            DB::statement('CREATE UNIQUE INDEX settings_single_active_unique ON settings (active_setting_guard)');
        } else {
            DB::statement('CREATE UNIQUE INDEX settings_single_active_unique ON settings (is_active) WHERE is_active');
        }
    }
};
