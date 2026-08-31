<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Services\InvoiceGenerator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UtilityReadingEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow('2026-10-31 12:00:00');

        $role = Role::create(['role_name' => 'Admin']);
        $this->admin = User::create([
            'name' => 'Admin Utility',
            'email' => 'utility-admin@example.com',
            'phone' => '0900000000',
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);
        $this->tenant = Tenant::create([
            'user_id' => $this->admin->id,
            'full_name' => 'Khách thuê kiểm thử',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'cccd' => '012345678901',
            'cccd_issue_date' => '2020-01-01',
            'cccd_issue_place' => 'Hà Nội',
            'phone' => '0911111111',
            'email' => 'utility-tenant@example.com',
            'address' => 'Hà Nội',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_entry_screen_uses_latest_reading_even_when_previous_month_is_missing(): void
    {
        $room = $this->createOccupiedRoom('P101');
        $this->createActiveContract($room, 'HD-101');
        UtilityReading::create([
            'room_id' => $room->id,
            'month' => 5,
            'year' => 2026,
            'record_date' => '2026-05-31',
            'electricity_old' => 100,
            'electricity_new' => 125,
            'water_old' => 40,
            'water_new' => 45,
            'status' => 'confirmed',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/utilities/create?month=7&year=2026')
            ->assertSuccessful()
            ->assertSee('Số cũ từ 5/2026')
            ->assertSee('<span class="elec-old">125</span>', false)
            ->assertSee('<span class="water-old">45</span>', false);
    }

    public function test_new_tenant_is_not_locked_by_previous_tenant_invoice_in_the_same_room_and_month(): void
    {
        $room = $this->createOccupiedRoom('TURNOVER-ROOM');
        $oldContract = $this->createActiveContract($room, 'HD-OLD-TURNOVER');
        $oldContract->forceFill([
            'status' => Contract::STATUS_TERMINATED,
            'end_date' => '2026-08-10',
            'actual_end_date' => '2026-08-10',
        ])->save();
        $oldReading = UtilityReading::create([
            'room_id' => $room->id, 'month' => 8, 'year' => 2026, 'record_date' => '2026-08-10',
            'reading_type' => 'periodic', 'electricity_old' => 0, 'electricity_new' => 125,
            'water_old' => 0, 'water_new' => 8, 'status' => 'confirmed',
        ]);
        Invoice::create([
            'contract_id' => $oldContract->id, 'room_id' => $room->id,
            'utility_reading_id' => $oldReading->id, 'invoice_code' => 'INV-202608-900001',
            'month' => 8, 'year' => 2026, 'invoice_date' => '2026-08-10', 'due_date' => '2026-08-20',
            'room_fee' => 3000000, 'total_amount' => 3000000, 'status' => Invoice::STATUS_UNPAID,
        ]);

        $newContract = $this->createActiveContract($room, 'HD-NEW-TURNOVER');
        $newContract->update(['start_date' => '2026-08-11']);
        $this->createHandover($room, $newContract, 2000, 10)->update(['record_date' => '2026-08-11']);

        $this->actingAs($this->admin)
            ->get('/admin/utilities/create?month=8&year=2026&record_date=2026-08-31')
            ->assertOk()
            ->assertViewHas('readings', function ($readings) use ($room) {
                $reading = collect($readings)->firstWhere('room_id', $room->id);

                return $reading
                    && $reading['electricity_old'] === 2000
                    && $reading['water_old'] === 10
                    && $reading['electricity_new'] === null
                    && $reading['locked'] === false;
            });
    }

    public function test_periodic_reading_uses_handover_in_the_same_period_and_closes_on_month_end(): void
    {
        $room = $this->createOccupiedRoom('SAME-DAY-HANDOVER');
        $contract = $this->createActiveContract($room, 'HD-SAME-DAY');
        $contract->update(['start_date' => '2026-08-11']);
        $this->createHandover($room, $contract, 1200, 150)->update([
            'month' => 8,
            'year' => 2026,
            'record_date' => '2026-08-11',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/utilities/create?month=8&year=2026&record_date=2026-08-11')
            ->assertOk()
            ->assertSee('31/08/2026')
            ->assertViewHas('readings', function ($readings) use ($room) {
                $reading = collect($readings)->firstWhere('room_id', $room->id);

                return $reading
                    && $reading['electricity_old'] === 1200
                    && $reading['water_old'] === 150;
            });

        $this->post('/admin/utilities/store', [
            'month' => 8,
            'year' => 2026,
            'record_date' => '2026-08-11',
            'readings' => [[
                'selected' => 1,
                'room_id' => $room->id,
                'electricity_new' => 1215,
                'water_new' => 153,
            ]],
        ])->assertRedirect('/admin/utilities?month=8&year=2026');

        $this->assertDatabaseHas('utility_readings', [
            'contract_id' => $contract->id,
            'reading_type' => 'periodic',
            'record_date' => '2026-08-31 00:00:00',
            'electricity_old' => 1200,
            'electricity_new' => 1215,
            'water_old' => 150,
            'water_new' => 153,
        ]);
    }

    public function test_current_period_can_be_confirmed_before_month_end_and_records_history(): void
    {
        Carbon::setTestNow('2026-08-27 10:00:00');
        $room = $this->createOccupiedRoom('P-CLOSING-DATE');
        $contract = $this->createActiveContract($room, 'HD-CLOSING-DATE');
        $this->createHandover($room, $contract, 100, 20);

        $payload = [
            'month' => 8,
            'year' => 2026,
            'record_date' => '2026-08-27',
            'readings' => [[
                'selected' => 1, 'room_id' => $room->id,
                'electricity_new' => 110, 'water_new' => 22,
            ]],
        ];

        $this->actingAs($this->admin)
            ->get('/admin/utilities/create?month=8&year=2026&record_date=2026-08-27')
            ->assertOk()
            ->assertSee('31/08/2026')
            ->assertSee('Lưu và xác nhận')
            ->assertDontSee('Chưa đến ngày chốt');

        $this->post('/admin/utilities/store', $payload)
            ->assertSessionHasNoErrors();
        $reading = UtilityReading::query()
            ->where('contract_id', $contract->id)
            ->where('reading_type', 'periodic')
            ->firstOrFail();
        $this->assertTrue($reading->isConfirmed());
        $this->assertSame('2026-08-31', $reading->record_date->toDateString());
        $this->assertDatabaseHas('utility_reading_histories', [
            'utility_reading_id' => $reading->id,
            'actor_id' => $this->admin->id,
            'action' => 'created_and_confirmed',
            'from_status' => null,
            'to_status' => UtilityReading::STATUS_CONFIRMED,
            'performed_at' => '2026-08-27 10:00:00',
        ]);
        $this->get('/admin/utilities?month=8&year=2026')
            ->assertOk()
            ->assertSee('Lịch sử thao tác (1)')
            ->assertSee($this->admin->name)
            ->assertSee('10:00 27/08/2026');
    }

    public function test_multiple_mid_period_checkpoints_do_not_replace_the_full_period_reading(): void
    {
        Carbon::setTestNow('2026-08-20 09:30:00');
        $room = $this->createOccupiedRoom('P-CHECKPOINT');
        $contract = $this->createActiveContract($room, 'HD-CHECKPOINT');
        $this->createHandover($room, $contract, 100, 20);

        $firstCheckpoint = [
            'month' => 8,
            'year' => 2026,
            'intent' => 'checkpoint',
            'reading_date' => '2026-08-15',
            'readings' => [[
                'selected' => 1,
                'room_id' => $room->id,
                'electricity_new' => 140,
                'water_new' => 25,
            ]],
        ];
        $this->actingAs($this->admin)->post('/admin/utilities/store', $firstCheckpoint)
            ->assertSessionHasNoErrors();

        $this->get('/admin/utilities/create?month=8&year=2026&mode=checkpoint&reading_date=2026-08-20')
            ->assertOk()
            ->assertSee('Ghi mốc giữa kỳ')
            ->assertSee('<span class="elec-old">140</span>', false)
            ->assertSee('<span class="water-old">25</span>', false);

        $secondCheckpoint = $firstCheckpoint;
        $secondCheckpoint['reading_date'] = '2026-08-20';
        $secondCheckpoint['readings'][0]['electricity_new'] = 160;
        $secondCheckpoint['readings'][0]['water_new'] = 28;
        $this->post('/admin/utilities/store', $secondCheckpoint)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('utility_readings', 3);
        $this->assertDatabaseHas('utility_readings', [
            'contract_id' => $contract->id,
            'reading_type' => 'interim',
            'record_date' => '2026-08-15 00:00:00',
            'electricity_old' => 100,
            'electricity_new' => 140,
            'water_old' => 20,
            'water_new' => 25,
        ]);
        $this->assertDatabaseHas('utility_readings', [
            'contract_id' => $contract->id,
            'reading_type' => 'interim',
            'record_date' => '2026-08-20 00:00:00',
            'electricity_old' => 140,
            'electricity_new' => 160,
            'water_old' => 25,
            'water_new' => 28,
        ]);

        $this->post('/admin/utilities/store', [
            'month' => 8,
            'year' => 2026,
            'intent' => 'confirm',
            'readings' => [[
                'selected' => 1,
                'room_id' => $room->id,
                'electricity_new' => 190,
                'water_new' => 32,
            ]],
        ])->assertSessionHasNoErrors();

        $periodic = UtilityReading::query()
            ->where('contract_id', $contract->id)
            ->where('reading_type', 'periodic')
            ->firstOrFail();
        $this->assertSame(100, $periodic->electricity_old);
        $this->assertSame(190, $periodic->electricity_new);
        $this->assertSame(20, $periodic->water_old);
        $this->assertSame(32, $periodic->water_new);

        $preview = app(InvoiceGenerator::class)->preview($contract, 9, 2026);
        $this->assertSame(90, collect($preview['lines'])->firstWhere('type', 'electricity')['quantity']);
        $this->assertSame(12, collect($preview['lines'])->firstWhere('type', 'water')['quantity']);

        $this->get('/admin/utilities?month=8&year=2026')
            ->assertOk()
            ->assertSee('Mốc đối soát giữa kỳ')
            ->assertSee('15/08/2026')
            ->assertSee('20/08/2026')
            ->assertSee('+40 kWh từ mốc trước')
            ->assertSee('+20 kWh từ mốc trước');

        $this->post('/admin/utilities/store', $secondCheckpoint)
            ->assertSessionHasErrors('reading_date');
        $this->assertSame(2, UtilityReading::query()->where('reading_type', 'interim')->count());

        app(InvoiceGenerator::class)->issue($contract, 9, 2026, $this->admin->id);
        $afterInvoice = $secondCheckpoint;
        $afterInvoice['reading_date'] = '2026-08-25';
        $afterInvoice['readings'][0]['electricity_new'] = 180;
        $afterInvoice['readings'][0]['water_new'] = 30;
        $this->post('/admin/utilities/store', $afterInvoice)
            ->assertSessionHasErrors('readings.0.room_id');
        $this->assertSame(2, UtilityReading::query()->where('reading_type', 'interim')->count());
    }

    public function test_only_admin_can_access_entry_pages_and_direct_store(): void
    {
        $this->get('/admin/utilities')->assertRedirect('/login');
        $this->post('/admin/utilities/store', [])->assertRedirect('/login');
        $clientRole = Role::create(['role_name' => 'User']);
        $client = User::create(['name' => 'Client', 'email' => 'utility-client@example.test',
            'role_id' => $clientRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $this->actingAs($client)->get('/admin/utilities')->assertForbidden();
        $this->post('/admin/utilities/store', [])->assertForbidden();
        $this->assertDatabaseCount('utility_readings', 0);
    }

    public function test_index_counts_distinct_rooms_and_excludes_non_periodic_readings_from_totals(): void
    {
        $room = $this->createOccupiedRoom('PERIODIC-STATS');
        $contract = $this->createActiveContract($room, 'HD-PERIODIC-STATS');

        foreach ([
            ['handover', '2026-08-01', 100, 100, 20, 20],
            ['periodic', '2026-08-20', 100, 120, 20, 25],
            ['checkout', '2026-08-31', 120, 130, 25, 27],
        ] as [$type, $date, $electricityOld, $electricityNew, $waterOld, $waterNew]) {
            UtilityReading::create([
                'room_id' => $room->id, 'contract_id' => $contract->id,
                'month' => 8, 'year' => 2026, 'record_date' => $date, 'reading_type' => $type,
                'electricity_old' => $electricityOld, 'electricity_new' => $electricityNew,
                'water_old' => $waterOld, 'water_new' => $waterNew, 'status' => 'confirmed',
            ]);
        }

        $this->actingAs($this->admin)
            ->get('/admin/utilities?month=8&year=2026')
            ->assertOk()
            ->assertViewHas('totalRooms', 1)
            ->assertViewHas('roomsRead', 1)
            ->assertViewHas('totalElectricity', 20)
            ->assertViewHas('totalWater', 5)
            ->assertViewHas('readings', fn ($readings) => $readings->count() === 1
                && $readings->first()->reading_type === 'periodic');
    }

    public function test_invalid_period_empty_selection_duplicate_room_and_nonexistent_room_are_rejected(): void
    {
        $room = $this->createOccupiedRoom('P-VALIDATION');
        $this->createActiveContract($room, 'HD-VALIDATION');
        $this->actingAs($this->admin)->get('/admin/utilities?month=13&year=1999')
            ->assertSessionHasErrors(['month', 'year']);
        $this->get('/admin/utilities/create?month=0')->assertSessionHasErrors('month');
        $this->post('/admin/utilities/store', ['month' => 8, 'year' => 2026, 'readings' => [[
            'room_id' => $room->id, 'electricity_new' => 1, 'water_new' => 1,
        ]]])->assertSessionHasErrors('readings');
        $this->post('/admin/utilities/store', ['month' => 8, 'year' => 2026, 'readings' => [
            ['selected' => 1, 'room_id' => $room->id, 'electricity_new' => 1, 'water_new' => 1],
            ['selected' => 1, 'room_id' => $room->id, 'electricity_new' => 2, 'water_new' => 2],
        ]])->assertSessionHasErrors('readings.0.room_id');
        $this->post('/admin/utilities/store', ['month' => 8, 'year' => 2026, 'readings' => [[
            'selected' => 1, 'room_id' => 999999, 'electricity_new' => 1, 'water_new' => 1,
        ]]])->assertSessionHasErrors('readings.0.room_id');
        $this->assertDatabaseCount('utility_readings', 0);
    }

    public function test_admin_can_save_only_the_rooms_completed_during_the_meter_round(): void
    {
        $firstRoom = $this->createOccupiedRoom('P201');
        $secondRoom = $this->createOccupiedRoom('P202');
        $firstContract = $this->createActiveContract($firstRoom, 'HD-201');
        $secondContract = $this->createActiveContract($secondRoom, 'HD-202');
        $this->createHandover($firstRoom, $firstContract);
        $this->createHandover($secondRoom, $secondContract);

        $response = $this->actingAs($this->admin)->post('/admin/utilities/store', [
            'month' => 8,
            'year' => 2026,
            'readings' => [
                [
                    'selected' => '1',
                    'room_id' => $firstRoom->id,
                    'electricity_new' => 150,
                    'water_new' => 30,
                ],
                [
                    'room_id' => $secondRoom->id,
                    'electricity_new' => null,
                    'water_new' => null,
                ],
            ],
        ]);

        $response->assertRedirect('/admin/utilities?month=8&year=2026');
        $this->assertDatabaseHas('utility_readings', [
            'room_id' => $firstRoom->id,
            'month' => 8,
            'year' => 2026,
            'electricity_new' => 150,
            'water_new' => 30,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseMissing('utility_readings', [
            'room_id' => $secondRoom->id,
            'month' => 8,
            'year' => 2026,
        ]);
    }

    public function test_reading_moves_from_draft_to_confirmed_to_locked_and_unlocks_when_empty_invoice_is_deleted(): void
    {
        $room = $this->createOccupiedRoom('P-LIFECYCLE');
        $contract = $this->createActiveContract($room, 'HD-LIFECYCLE');
        $this->createHandover($room, $contract);

        $this->actingAs($this->admin)->post('/admin/utilities/store', [
            'month' => 8,
            'year' => 2026,
            'intent' => 'draft',
            'readings' => [[
                'selected' => 1,
                'room_id' => $room->id,
                'electricity_new' => 150,
                'water_new' => 30,
            ]],
        ])->assertRedirect('/admin/utilities?month=8&year=2026');

        $reading = UtilityReading::query()->where('room_id', $room->id)->where('reading_type', 'periodic')->firstOrFail();
        $this->assertTrue($reading->isDraft());
        $this->getJson("/admin/invoices/contracts/{$contract->id}/preview?month=9&year=2026")
            ->assertUnprocessable();

        $this->post(route('admin.utilities.confirm', $reading))->assertSessionHasNoErrors();
        $this->assertTrue($reading->fresh()->isConfirmed());

        $this->post('/admin/utilities/store', [
            'month' => 8,
            'year' => 2026,
            'readings' => [[
                'selected' => 1,
                'room_id' => $room->id,
                'electricity_new' => 151,
                'water_new' => 31,
            ]],
        ])->assertSessionHasErrors('readings.0.room_id');

        $invoice = app(InvoiceGenerator::class)->issue($contract, 9, 2026);
        $this->assertTrue($reading->fresh()->isLocked());
        $this->post(route('admin.utilities.reopen', $reading))->assertSessionHasErrors('reading');

        $this->post(route('admin.invoices.cancel', $invoice), [
            'cancellation_reason' => 'Hóa đơn được lập thử để kiểm tra quy trình.',
        ])->assertSessionHas('success');
        $this->assertTrue($reading->fresh()->isConfirmed());

        $this->post(route('admin.utilities.reopen', $reading))->assertSessionHasNoErrors();
        $this->assertTrue($reading->fresh()->isDraft());
        $this->assertSame(
            ['draft_created', 'confirmed', 'reopened'],
            $reading->histories()->pluck('action')->all(),
        );
        $this->assertTrue(
            $reading->histories()->get()->every(
                fn ($history) => $history->actor_id === $this->admin->id
                    && $history->performed_at !== null
                    && is_array($history->snapshot),
            ),
        );
    }

    public function test_periods_must_be_confirmed_month_by_month_and_reopened_in_reverse_order(): void
    {
        $room = $this->createOccupiedRoom('P-SEQUENTIAL');
        $contract = $this->createActiveContract($room, 'HD-SEQUENTIAL');
        $this->createHandover($room, $contract, 100, 20);
        UtilityReading::create([
            'room_id' => $room->id, 'contract_id' => $contract->id,
            'month' => 7, 'year' => 2026, 'record_date' => '2026-07-31', 'reading_type' => 'periodic',
            'electricity_old' => 100, 'electricity_new' => 110,
            'water_old' => 20, 'water_new' => 21, 'status' => UtilityReading::STATUS_CONFIRMED,
        ]);

        $this->actingAs($this->admin)->post('/admin/utilities/store', [
            'month' => 9,
            'year' => 2026,
            'intent' => 'draft',
            'readings' => [[
                'selected' => 1, 'room_id' => $room->id,
                'electricity_new' => 120, 'water_new' => 25,
            ]],
        ])->assertSessionHasNoErrors();

        $september = UtilityReading::query()
            ->where('contract_id', $contract->id)
            ->where('month', 9)
            ->where('year', 2026)
            ->firstOrFail();

        $this->post(route('admin.utilities.confirm', $september))
            ->assertSessionHasErrors('reading');
        $this->assertTrue($september->fresh()->isDraft());

        $this->post('/admin/utilities/store', [
            'month' => 8,
            'year' => 2026,
            'readings' => [[
                'selected' => 1, 'room_id' => $room->id,
                'electricity_new' => 110, 'water_new' => 21,
            ]],
        ])->assertSessionHasNoErrors();

        $august = UtilityReading::query()
            ->where('contract_id', $contract->id)
            ->where('month', 8)
            ->where('year', 2026)
            ->firstOrFail();
        $this->assertTrue($august->isConfirmed());

        $this->post(route('admin.utilities.confirm', $september))
            ->assertSessionHasNoErrors();
        $this->assertTrue($september->fresh()->isConfirmed());

        $this->post(route('admin.utilities.reopen', $august))
            ->assertSessionHasErrors('reading');
        $this->assertTrue($august->fresh()->isConfirmed());

        $this->post(route('admin.utilities.reopen', $september))
            ->assertSessionHasNoErrors();
        $this->post(route('admin.utilities.reopen', $august))
            ->assertSessionHasNoErrors();
        $this->assertTrue($august->fresh()->isDraft());
    }

    public function test_reading_linked_to_an_invoice_cannot_be_changed(): void
    {
        $room = $this->createOccupiedRoom('P301');
        $contract = $this->createActiveContract($room, 'HD-301');
        $reading = UtilityReading::create([
            'room_id' => $room->id,
            'month' => 9,
            'year' => 2026,
            'record_date' => '2026-09-30',
            'electricity_old' => 100,
            'electricity_new' => 130,
            'water_old' => 20,
            'water_new' => 25,
            'status' => 'confirmed',
        ]);
        Invoice::create([
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'utility_reading_id' => $reading->id,
            'invoice_code' => 'INV-LOCKED',
            'month' => 9,
            'year' => 2026,
            'invoice_date' => '2026-09-30',
            'due_date' => '2026-10-10',
            'room_fee' => 3000000,
            'electricity_fee' => 100000,
            'water_fee' => 50000,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 3150000,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->admin)
            ->from('/admin/utilities/create?month=9&year=2026')
            ->post('/admin/utilities/store', [
                'month' => 9,
                'year' => 2026,
                'readings' => [[
                    'selected' => '1',
                    'room_id' => $room->id,
                    'electricity_new' => 999,
                    'water_new' => 999,
                ]],
            ])
            ->assertRedirect('/admin/utilities/create?month=9&year=2026')
            ->assertSessionHasErrors('readings.0.room_id');

        $this->assertDatabaseHas('utility_readings', [
            'id' => $reading->id,
            'electricity_new' => 130,
            'water_new' => 25,
        ]);
    }

    public function test_decreasing_reading_ineligible_room_and_incomplete_selected_row_change_nothing(): void
    {
        $room = $this->createOccupiedRoom('P400');
        $this->createActiveContract($room, 'HD-400');
        UtilityReading::create(['room_id' => $room->id, 'month' => 7, 'year' => 2026,
            'record_date' => '2026-07-31', 'electricity_old' => 90, 'electricity_new' => 100,
            'water_old' => 10, 'water_new' => 20, 'status' => 'confirmed']);

        $this->actingAs($this->admin)->post('/admin/utilities/store', ['month' => 8, 'year' => 2026, 'readings' => [[
            'selected' => 1, 'room_id' => $room->id, 'electricity_new' => 99, 'water_new' => 19,
        ]]])->assertSessionHasErrors('readings.0.electricity_new');
        $this->post('/admin/utilities/store', ['month' => 8, 'year' => 2026, 'readings' => [[
            'selected' => 1, 'room_id' => $room->id, 'electricity_new' => null, 'water_new' => 25,
        ]]])->assertSessionHasErrors('readings.0.electricity_new');
        $room->update(['status' => Room::STATUS_MAINTENANCE]);
        $this->post('/admin/utilities/store', ['month' => 8, 'year' => 2026, 'readings' => [[
            'selected' => 1, 'room_id' => $room->id, 'electricity_new' => 110, 'water_new' => 25,
        ]]])->assertSessionHasErrors('readings.0.room_id');
        $this->assertDatabaseMissing('utility_readings', ['room_id' => $room->id, 'month' => 8, 'year' => 2026]);
    }

    public function test_earlier_period_cannot_break_the_opening_values_of_a_later_period(): void
    {
        $room = $this->createOccupiedRoom('P500');
        $contract = $this->createActiveContract($room, 'HD-500');
        $this->createHandover($room, $contract);
        UtilityReading::create(['room_id' => $room->id, 'month' => 9, 'year' => 2026,
            'record_date' => '2026-09-30', 'electricity_old' => 150, 'electricity_new' => 170,
            'water_old' => 30, 'water_new' => 35, 'status' => 'confirmed']);
        $this->actingAs($this->admin)->post('/admin/utilities/store', ['month' => 8, 'year' => 2026, 'readings' => [[
            'selected' => 1, 'room_id' => $room->id, 'electricity_new' => 149, 'water_new' => 29,
        ]]])->assertSessionHasErrors('readings.0.electricity_new');
        $this->assertDatabaseMissing('utility_readings', ['room_id' => $room->id, 'month' => 8]);
    }

    public function test_meter_images_are_replaced_after_commit_and_new_files_are_cleaned_on_rollback(): void
    {
        Storage::fake('local');
        $first = $this->createOccupiedRoom('P601');
        $second = $this->createOccupiedRoom('P602');
        $firstContract = $this->createActiveContract($first, 'HD-601');
        $this->createActiveContract($second, 'HD-602');
        $this->createHandover($first, $firstContract);
        Storage::disk('local')->put('utility-readings/electricity/old.jpg', 'old');
        UtilityReading::create(['room_id' => $first->id, 'month' => 8, 'year' => 2026,
            'record_date' => '2026-08-31', 'electricity_old' => 0, 'electricity_new' => 10,
            'electricity_image' => 'utility-readings/electricity/old.jpg', 'water_old' => 0,
            'water_new' => 10, 'status' => 'draft']);
        UtilityReading::create(['room_id' => $second->id, 'month' => 7, 'year' => 2026,
            'record_date' => '2026-07-31', 'electricity_old' => 0, 'electricity_new' => 10,
            'water_old' => 0, 'water_new' => 10, 'status' => 'confirmed']);

        $this->actingAs($this->admin)->post('/admin/utilities/store', ['month' => 8, 'year' => 2026,
            'readings' => [
                ['selected' => 1, 'room_id' => $first->id, 'electricity_new' => 12, 'water_new' => 12,
                    'electricity_image' => UploadedFile::fake()->image('new.jpg')],
                ['selected' => 1, 'room_id' => $second->id, 'electricity_new' => 5, 'water_new' => 5],
            ]])->assertSessionHasErrors('readings.1.electricity_new');
        Storage::disk('local')->assertExists('utility-readings/electricity/old.jpg');
        $this->assertCount(1, Storage::disk('local')->allFiles());

        $this->post('/admin/utilities/store', ['month' => 8, 'year' => 2026, 'readings' => [[
            'selected' => 1, 'room_id' => $first->id, 'electricity_new' => 12, 'water_new' => 12,
            'electricity_image' => UploadedFile::fake()->image('replacement.jpg'),
        ]]])->assertRedirect('/admin/utilities?month=8&year=2026');
        Storage::disk('local')->assertMissing('utility-readings/electricity/old.jpg');
        Storage::disk('local')->assertExists(UtilityReading::where('room_id', $first->id)->value('electricity_image'));
    }

    public function test_invalid_or_oversized_meter_images_are_rejected_without_files_or_rows(): void
    {
        Storage::fake('local');
        $room = $this->createOccupiedRoom('P-IMAGE-INVALID');
        $this->createActiveContract($room, 'HD-IMAGE-INVALID');
        $base = ['month' => 8, 'year' => 2026, 'readings' => [[
            'selected' => 1, 'room_id' => $room->id, 'electricity_new' => 10, 'water_new' => 10,
        ]]];

        $invalid = $base;
        $invalid['readings'][0]['electricity_image'] = UploadedFile::fake()->create('meter.pdf', 20, 'application/pdf');
        $this->actingAs($this->admin)->post('/admin/utilities/store', $invalid)
            ->assertSessionHasErrors('readings.0.electricity_image');

        $oversized = $base;
        $oversized['readings'][0]['water_image'] = UploadedFile::fake()->image('water.jpg')->size(5000);
        $this->post('/admin/utilities/store', $oversized)
            ->assertSessionHasErrors('readings.0.water_image');

        $this->assertDatabaseCount('utility_readings', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_database_rejects_invalid_period_and_meter_sequences_even_for_direct_sql(): void
    {
        $room = $this->createOccupiedRoom('P-DB-CHECK');
        $base = [
            'room_id' => $room->id, 'month' => 8, 'year' => 2026, 'record_date' => '2026-08-31',
            'electricity_old' => 10, 'electricity_new' => 20, 'water_old' => 5, 'water_new' => 8,
            'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now(),
        ];

        foreach ([
            ['month' => 0], ['month' => 13], ['year' => 1999], ['year' => 2101],
            ['electricity_old' => -1], ['electricity_new' => 9],
            ['water_old' => -1], ['water_new' => 4],
        ] as $invalid) {
            try {
                DB::table('utility_readings')->insert(array_merge($base, $invalid));
                $this->fail('Database accepted an invalid utility reading.');
            } catch (QueryException) {
                $this->assertDatabaseCount('utility_readings', 0);
            }
        }

        DB::table('utility_readings')->insert($base);
        $this->assertDatabaseHas('utility_readings', [
            'room_id' => $room->id, 'month' => 8, 'year' => 2026,
            'electricity_old' => 10, 'electricity_new' => 20, 'water_old' => 5, 'water_new' => 8,
        ]);

        $this->expectException(QueryException::class);
        DB::table('utility_readings')->where('room_id', $room->id)->update(['electricity_new' => 9]);
    }

    private function createOccupiedRoom(string $code): Room
    {
        return Room::create([
            'room_code' => $code,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'max_people' => 4,
            'current_people' => 1,
            'status' => 'occupied',
        ]);
    }

    private function createActiveContract(Room $room, string $code): Contract
    {
        return Contract::query()->forceCreate([
            'contract_code' => $code,
            'room_id' => $room->id,
            'tenant_id' => $this->tenant->id,
            'monthly_rent' => 3000000,
            'deposit_amount' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
    }

    private function createHandover(Room $room, Contract $contract, int $electricity = 0, int $water = 0): UtilityReading
    {
        return UtilityReading::create([
            'room_id' => $room->id, 'contract_id' => $contract->id,
            'month' => 1, 'year' => 2026, 'record_date' => '2026-01-01', 'reading_type' => 'handover',
            'electricity_old' => $electricity, 'electricity_new' => $electricity,
            'water_old' => $water, 'water_new' => $water, 'status' => 'confirmed',
        ]);
    }
}
