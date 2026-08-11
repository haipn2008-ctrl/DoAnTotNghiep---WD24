<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $adminRole = Role::create(['role_name' => 'Admin']);
        $this->clientRole = Role::create(['role_name' => 'User']);
        $this->admin = $this->user($adminRole, 'contract-admin@example.test');
    }

    public function test_only_admin_can_access_contract_pages_and_direct_mutations(): void
    {
        [$contract] = $this->contract();
        $this->get('/admin/contracts')->assertRedirect('/login');
        $client = $this->user($this->clientRole, 'contract-client@example.test');
        $this->actingAs($client)->get('/admin/contracts')->assertForbidden();
        $this->post('/admin/contracts', [])->assertForbidden();
        $this->put("/admin/contracts/{$contract->id}", [])->assertForbidden();
        $this->post("/admin/contracts/{$contract->id}/extend", [])->assertForbidden();
        $this->post("/admin/contracts/{$contract->id}/end", [])->assertForbidden();
        $this->delete("/admin/contracts/{$contract->id}")->assertForbidden();
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
    }

    public function test_admin_can_search_filter_view_and_print_while_invalid_filters_and_ids_are_rejected(): void
    {
        [$contract, $room, $tenant] = $this->contract();
        $this->actingAs($this->admin)->get('/admin/contracts?keyword='.urlencode($tenant->full_name).'&status=active')
            ->assertOk()->assertSee($contract->contract_code)->assertSee($room->room_code);
        $this->get("/admin/contracts/{$contract->id}")->assertOk()->assertSee($tenant->cccd);
        $this->get("/admin/contracts/{$contract->id}/print")->assertOk()->assertSee($tenant->full_name);
        $this->get('/admin/contracts?status=fake')->assertSessionHasErrors('status');
        $this->get('/admin/contracts/999999')->assertNotFound();
    }

    public function test_admin_can_issue_one_deposit_invoice_from_contract_detail_and_client_can_see_it(): void
    {
        [$contract, , $tenant] = $this->contract();
        $contract->update(['deposit_amount' => 2000000]);

        $this->actingAs($this->admin)->get("/admin/contracts/{$contract->id}")
            ->assertOk()->assertSee('Tạo hóa đơn tiền cọc');

        $response = $this->post("/admin/contracts/{$contract->id}/deposit-invoice");
        $invoice = Invoice::where('contract_id', $contract->id)
            ->where('invoice_type', Invoice::TYPE_DEPOSIT)->sole();

        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertSame('2000000.00', $invoice->total_amount);
        $this->assertStringStartsWith('DEP-', $invoice->invoice_code);
        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $invoice->id, 'type' => Invoice::TYPE_DEPOSIT, 'amount' => 2000000,
        ]);

        $this->post("/admin/contracts/{$contract->id}/deposit-invoice")
            ->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertSame(1, $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->count());

        $this->actingAs($tenant->user)->get(route('client.invoices.show', $invoice))->assertOk();
    }

    public function test_paying_deposit_invoice_updates_contract_deposit_status(): void
    {
        [$contract] = $this->contract();
        $contract->update(['deposit_amount' => 1500000]);
        $this->actingAs($this->admin)->post("/admin/contracts/{$contract->id}/deposit-invoice");
        $invoice = $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->sole();

        $this->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 1500000,
            'payment_date' => now()->toDateString(),
            'payment_method' => Payment::METHOD_CASH,
        ])->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertSame(Contract::DEPOSIT_PAID, $contract->fresh()->deposit_status);
        $this->assertNotNull($contract->fresh()->deposit_paid_at);
    }

    public function test_creating_contract_immediately_activates_it_and_occupies_room_atomically(): void
    {
        $tenant = $this->tenant('create');
        $room = $this->room(['room_code' => 'CREATE', 'max_people' => 6]);
        $this->actingAs($this->admin)->post('/admin/contracts', $this->payload($room, $tenant, [
            'number_of_people' => 5,
        ]))->assertRedirect(route('admin.contracts.index'))->assertSessionHas('success');

        $contract = Contract::where('room_id', $room->id)->firstOrFail();
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->status);
        $this->assertSame('HD'.str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT), $contract->contract_code);
        $this->assertNotNull($contract->signed_at);
        $this->assertSame(Room::STATUS_OCCUPIED, $room->fresh()->status);
        $this->assertSame(5, $room->fresh()->current_people);
    }

    public function test_create_rejects_invalid_dates_capacity_room_tenant_and_repeated_assignment_without_partial_writes(): void
    {
        $tenant = $this->tenant('invalid');
        $room = $this->room(['room_code' => 'INVALID', 'max_people' => 2]);
        $this->actingAs($this->admin)->post('/admin/contracts', $this->payload($room, $tenant, [
            'end_date' => '2026-01-01', 'number_of_people' => 3,
        ]))->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseCount('contracts', 0);
        $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);

        $this->post('/admin/contracts', $this->payload($room, $tenant, ['number_of_people' => 3]))
            ->assertSessionHasErrors('number_of_people');
        $this->assertDatabaseCount('contracts', 0);

        $room->update(['status' => Room::STATUS_MAINTENANCE]);
        $this->post('/admin/contracts', $this->payload($room, $tenant))->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('contracts', 0);

        $room->update(['status' => Room::STATUS_AVAILABLE]);
        $tenant->user->update(['status' => User::STATUS_INACTIVE]);
        $this->post('/admin/contracts', $this->payload($room, $tenant))->assertSessionHasErrors('tenant_id');
        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_create_form_suggests_latest_room_reading_and_rejects_a_lower_handover(): void
    {
        $oldTenant = $this->tenant('handover-old');
        $newTenant = $this->tenant('handover-new');
        $room = $this->room(['room_code' => 'HANDOVER-SUGGESTION']);
        $oldContract = Contract::create([
            'contract_code' => 'HD-HANDOVER-OLD', 'room_id' => $room->id,
            'tenant_id' => $oldTenant->id, 'monthly_rent' => 3000000,
            'start_date' => '2026-01-01', 'end_date' => '2026-08-10',
            'actual_end_date' => '2026-08-10', 'status' => Contract::STATUS_TERMINATED,
        ]);
        UtilityReading::create([
            'room_id' => $room->id, 'contract_id' => $oldContract->id,
            'month' => 8, 'year' => 2026, 'record_date' => '2026-08-10', 'reading_type' => 'checkout',
            'electricity_old' => 1240, 'electricity_new' => 1250,
            'water_old' => 158, 'water_new' => 160, 'status' => 'confirmed',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/contracts/create')
            ->assertOk()
            ->assertSee('data-electricity="1250" data-water="160"', false);

        $this->post('/admin/contracts', $this->payload($room, $newTenant, [
            'handover_electricity' => 1249,
            'handover_water' => 159,
        ]))->assertSessionHasErrors(['handover_electricity', 'handover_water']);

        $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
        $this->assertDatabaseCount('contracts', 1);
        $this->assertDatabaseCount('utility_readings', 1);
    }

    public function test_update_validates_unique_tenant_identity_and_changes_no_contract_financial_data(): void
    {
        [$contract, , $tenant] = $this->contract();
        $other = $this->tenant('other');
        $oldRent = $contract->monthly_rent;
        $this->actingAs($this->admin)->put("/admin/contracts/{$contract->id}", [
            'full_name' => 'Changed', 'cccd' => $other->cccd, 'phone' => $other->phone,
            'email' => $other->email, 'address' => 'New address',
        ])->assertSessionHasErrors(['cccd', 'phone', 'email']);
        $this->assertSame($tenant->full_name, $tenant->fresh()->full_name);
        $this->assertEquals($oldRent, $contract->fresh()->monthly_rent);
    }

    public function test_extend_requires_active_contract_valid_reason_and_later_date_and_records_history(): void
    {
        [$contract] = $this->contract();
        $this->actingAs($this->admin)->post("/admin/contracts/{$contract->id}/extend", [
            'new_end_date' => '2027-06-30', 'extend_reason' => 'agreement',
        ])->assertSessionHasErrors('confirm_extend');
        $this->actingAs($this->admin)->post("/admin/contracts/{$contract->id}/extend", [
            'new_end_date' => '2026-12-31', 'extend_reason' => 'fake', 'confirm_extend' => '1',
        ])->assertSessionHasErrors('extend_reason');
        $this->post("/admin/contracts/{$contract->id}/extend", [
            'new_end_date' => '2026-12-31', 'extend_reason' => 'agreement', 'confirm_extend' => '1',
        ])->assertSessionHasErrors('new_end_date');
        $this->post("/admin/contracts/{$contract->id}/extend", [
            'new_end_date' => '2027-06-30', 'extend_reason' => 'agreement', 'extend_note' => 'Agreed', 'confirm_extend' => '1',
        ])->assertRedirect(route('admin.contracts.extend.list'));
        $contract->refresh();
        $this->assertSame('2027-06-30', $contract->end_date->toDateString());
        $this->assertSame('2027-01-01', $contract->extend_start_date->toDateString());
        $this->assertSame('2027-06-30', $contract->extend_end_date->toDateString());
        $this->assertNotNull($contract->extended_at);

        $contract->update(['status' => Contract::STATUS_TERMINATED]);
        $this->get("/admin/contracts/{$contract->id}/extend-form")->assertStatus(409);
        $this->post("/admin/contracts/{$contract->id}/extend", [
            'new_end_date' => '2028-01-01', 'extend_reason' => 'other', 'confirm_extend' => '1',
        ])->assertSessionHasErrors('contract');
    }

    public function test_only_legacy_pending_contract_can_be_deleted_and_repeat_returns_not_found(): void
    {
        [$active] = $this->contract();
        $this->actingAs($this->admin)->delete("/admin/contracts/{$active->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('contracts', ['id' => $active->id]);

        [$pending] = $this->contract(Contract::STATUS_PENDING);
        $this->delete("/admin/contracts/{$pending->id}")->assertRedirect(route('admin.contracts.index'));
        $this->assertDatabaseMissing('contracts', ['id' => $pending->id]);
        $this->delete("/admin/contracts/{$pending->id}")->assertNotFound();
    }

    public function test_direct_end_request_requires_confirmation_and_changes_nothing_when_missing(): void
    {
        [$contract, $room] = $this->contract();
        $this->actingAs($this->admin)->post("/admin/contracts/{$contract->id}/end", [
            'actual_end_date' => '2026-08-10', 'termination_reason' => 'early',
        ])->assertSessionHasErrors('confirm_end');
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $room->fresh()->status);
    }

    public function test_end_form_prefills_latest_readings_from_the_same_contract(): void
    {
        [$contract, $room] = $this->contract();
        UtilityReading::create([
            'room_id' => $room->id, 'contract_id' => $contract->id,
            'month' => 8, 'year' => 2026, 'record_date' => '2026-08-10', 'reading_type' => 'periodic',
            'electricity_old' => 100, 'electricity_new' => 1240,
            'water_old' => 50, 'water_new' => 158, 'status' => 'confirmed',
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/contracts/{$contract->id}/end-form")
            ->assertOk()
            ->assertViewHas('latestReading', fn ($reading) => $reading->contract_id === $contract->id)
            ->assertSee('value="1240"', false)
            ->assertSee('value="158"', false)
            ->assertSee('Gần nhất: 1240 kWh')
            ->assertSee('Gần nhất: 158 m³');
    }

    private function payload(Room $room, Tenant $tenant, array $overrides = []): array
    {
        return array_merge(['room_id' => $room->id, 'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
            'deposit_amount' => 1000000, 'number_of_people' => 1,
            'handover_electricity' => 100, 'handover_water' => 50], $overrides);
    }

    private function contract(string $status = Contract::STATUS_ACTIVE): array
    {
        static $sequence = 0;
        $sequence++;
        $tenant = $this->tenant('contract'.$sequence);
        $room = $this->room(['room_code' => 'CONTRACT-'.$sequence,
            'status' => $status === Contract::STATUS_ACTIVE ? Room::STATUS_OCCUPIED : Room::STATUS_AVAILABLE,
            'current_people' => $status === Contract::STATUS_ACTIVE ? 1 : 0]);
        $contract = Contract::create(['contract_code' => 'TEST-HD-'.$sequence, 'room_id' => $room->id,
            'tenant_id' => $tenant->id, 'monthly_rent' => 3000000, 'number_of_people' => 1,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => $status]);
        UtilityReading::create(['room_id' => $room->id, 'contract_id' => $contract->id,
            'month' => 1, 'year' => 2026, 'record_date' => '2026-01-01', 'reading_type' => 'handover',
            'electricity_old' => 100, 'electricity_new' => 100, 'water_old' => 50, 'water_new' => 50,
            'status' => 'confirmed']);

        return [$contract, $room, $tenant];
    }

    private function room(array $overrides = []): Room
    {
        return Room::create(array_merge(['room_code' => uniqid('ROOM-'), 'floor' => 1, 'price' => 3000000,
            'area' => 25, 'max_people' => 4, 'current_people' => 0, 'status' => Room::STATUS_AVAILABLE], $overrides));
    }

    private function tenant(string $key): Tenant
    {
        $user = $this->user($this->clientRole, $key.'@example.test');

        return Tenant::create(['user_id' => $user->id, 'full_name' => 'Tenant '.$key,
            'cccd' => str_pad((string) abs(crc32($key)), 12, '0'), 'phone' => '09'.str_pad((string) abs(crc32('p'.$key)), 8, '0'),
            'email' => 'tenant-'.$key.'@example.test']);
    }

    private function user(Role $role, string $email): User
    {
        return User::create(['name' => 'User', 'email' => $email, 'role_id' => $role->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE]);
    }
}
