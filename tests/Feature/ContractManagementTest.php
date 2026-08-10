<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
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

    private function payload(Room $room, Tenant $tenant, array $overrides = []): array
    {
        return array_merge(['room_id' => $room->id, 'tenant_id' => $tenant->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
            'deposit_amount' => 1000000, 'number_of_people' => 1], $overrides);
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
