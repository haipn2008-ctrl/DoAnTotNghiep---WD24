<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyContractLifecycleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_backfill_maps_states_and_preserves_related_records(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.lifecycle_legacy', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection('lifecycle_legacy');

        try {
            $this->createLegacySchema();
            $now = '2026-08-11 10:00:00';
            DB::table('users')->insert(['id' => 1]);
            DB::table('settings')->insert([
                'id' => 1, 'electric_price' => 3500, 'water_price' => 20000,
                'internet_fee' => 100000, 'service_fee' => 50000, 'payment_due_days' => 10,
                'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ([1, 2, 3, 4] as $id) {
                DB::table('rooms')->insert(['id' => $id]);
                DB::table('tenants')->insert(['id' => $id]);
            }

            $this->insertLegacyContract(1, 'LEGACY-ACTIVE', 'active', 1, 0, 'pending', $now);
            $this->insertLegacyContract(2, 'LEGACY-PENDING', 'pending', 2, 0, 'pending', $now);
            $this->insertLegacyContract(3, 'LEGACY-TERMINATED', 'terminated', 3, 1000000, 'paid', $now, [
                'terminated_at' => '2026-08-01', 'actual_end_date' => '2026-08-01',
                'termination_reason' => 'legacy_checkout',
            ]);
            $this->insertLegacyContract(4, 'LEGACY-CLOSED', 'terminated', 4, 1000000, 'returned', $now, [
                'terminated_at' => '2026-07-01', 'actual_end_date' => '2026-07-01',
                'termination_reason' => 'legacy_completed',
            ]);
            DB::table('invoices')->insert([
                'id' => 91, 'contract_id' => 3, 'room_id' => 3, 'invoice_type' => 'rental',
                'status' => 'unpaid', 'total_amount' => 500000, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('payments')->insert([
                'id' => 92, 'invoice_id' => 91, 'amount_paid' => 100000, 'status' => 'success',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('utility_readings')->insert([
                'id' => 93, 'room_id' => 1, 'contract_id' => 1, 'reading_type' => 'handover',
                'created_at' => $now, 'updated_at' => $now,
            ]);

            $migration = require database_path('migrations/2026_08_11_000006_implement_contract_lifecycle.php');
            $migration->up();

            $active = DB::table('contracts')->find(1);
            $this->assertSame('active', $active->status);
            $this->assertNotNull($active->signed_at);
            $this->assertStringStartsWith('2026-01-01', $active->actual_move_in_at);
            $activeHistory = DB::table('contract_status_histories')->where('contract_id', 1)->sole();
            $this->assertTrue(json_decode($activeHistory->metadata, true)['migrated']);

            $pending = DB::table('contracts')->find(2);
            $this->assertSame('draft', $pending->status);
            $this->assertNull($pending->signed_at);
            $this->assertNull($pending->actual_move_in_at);

            $terminated = DB::table('contracts')->find(3);
            $this->assertSame('settling', $terminated->status);
            $this->assertNotNull($terminated->actual_move_out_at);
            $terminatedHistory = DB::table('contract_status_histories')->where('contract_id', 3)->sole();
            $this->assertTrue(json_decode($terminatedHistory->metadata, true)['requires_admin_review']);

            $completed = DB::table('contracts')->find(4);
            $this->assertSame('completed', $completed->status);
            $this->assertNotNull($completed->completed_at);
            $this->assertSame('refunded', $completed->deposit_resolution);

            $this->assertTrue(DB::table('invoices')->where('id', 91)->where('contract_id', 3)->exists());
            $this->assertTrue(DB::table('payments')->where('id', 92)->where('invoice_id', 91)->exists());
            $this->assertTrue(DB::table('utility_readings')->where('id', 93)->where('contract_id', 1)->exists());
        } finally {
            DB::disconnect('lifecycle_legacy');
            DB::setDefaultConnection($originalConnection);
        }
    }

    private function createLegacySchema(): void
    {
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('rooms', fn (Blueprint $table) => $table->id());
        Schema::create('tenants', fn (Blueprint $table) => $table->id());
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->decimal('electric_price', 10, 2);
            $table->decimal('water_price', 10, 2);
            $table->decimal('internet_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->unsignedTinyInteger('payment_due_days')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_code')->unique();
            $table->foreignId('room_id')->constrained();
            $table->foreignId('tenant_id')->constrained();
            $table->decimal('monthly_rent', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->string('deposit_status')->default('pending');
            $table->dateTime('deposit_paid_at')->nullable();
            $table->unsignedInteger('number_of_people')->default(1);
            $table->date('signed_at')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('terminated_at')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->text('termination_note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained();
            $table->foreignId('room_id')->constrained();
            $table->string('invoice_type')->default('rental');
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->default('unpaid');
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->decimal('amount_paid', 12, 2);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('utility_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained();
            $table->foreignId('contract_id')->nullable()->constrained();
            $table->string('reading_type')->default('periodic');
            $table->timestamps();
        });
    }

    private function insertLegacyContract(
        int $id,
        string $code,
        string $status,
        int $relationId,
        float $deposit,
        string $depositStatus,
        string $now,
        array $extra = []
    ): void {
        DB::table('contracts')->insert(array_merge([
            'id' => $id, 'contract_code' => $code, 'room_id' => $relationId, 'tenant_id' => $relationId,
            'monthly_rent' => 3000000, 'deposit_amount' => $deposit, 'deposit_status' => $depositStatus,
            'number_of_people' => 1, 'signed_at' => null, 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'status' => $status, 'created_at' => $now, 'updated_at' => $now,
        ], $extra));
    }
}
