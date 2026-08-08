<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_admin_can_save_only_the_rooms_completed_during_the_meter_round(): void
    {
        $firstRoom = $this->createOccupiedRoom('P201');
        $secondRoom = $this->createOccupiedRoom('P202');
        $this->createActiveContract($firstRoom, 'HD-201');
        $this->createActiveContract($secondRoom, 'HD-202');

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
        return Contract::create([
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
}
