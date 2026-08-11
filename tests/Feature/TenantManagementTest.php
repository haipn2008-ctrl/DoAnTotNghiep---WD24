<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $clientRole;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->adminRole = Role::create(['id' => 10, 'role_name' => 'Admin']);
        $this->clientRole = Role::create(['id' => 20, 'role_name' => 'User']);
        $this->admin = $this->user($this->adminRole, 'admin@example.com');
    }

    public function test_only_admin_can_access_tenant_crud_and_direct_mutations(): void
    {
        $tenant = $this->tenant($this->user($this->clientRole, 'target@example.com'));

        $this->get('/admin/tenants')->assertRedirect('/login');
        $this->post('/admin/tenants', [])->assertRedirect('/login');
        $this->put("/admin/tenants/{$tenant->id}", [])->assertRedirect('/login');
        $this->delete("/admin/tenants/{$tenant->id}")->assertRedirect('/login');

        $client = $this->user($this->clientRole, 'client@example.com');
        $this->actingAs($client)->get('/admin/tenants')->assertForbidden();
        $this->post('/admin/tenants', [])->assertForbidden();
        $this->put("/admin/tenants/{$tenant->id}", [])->assertForbidden();
        $this->delete("/admin/tenants/{$tenant->id}")->assertForbidden();
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_admin_can_list_and_search_by_identity_or_contact_fields(): void
    {
        $alice = $this->tenant($this->user($this->clientRole, 'alice-account@example.com'), [
            'full_name' => 'Alice Unique',
            'cccd' => '079000000101',
            'phone' => '0900000101',
            'email' => 'alice.tenant@example.com',
        ]);
        $bob = $this->tenant($this->user($this->clientRole, 'bob-account@example.com'), [
            'full_name' => 'Bob Separate',
            'cccd' => '079000000102',
            'phone' => '0900000102',
            'email' => 'bob.tenant@example.com',
        ]);

        $this->actingAs($this->admin)->get('/admin/tenants')->assertOk()->assertSee($alice->cccd)->assertSee($bob->cccd);

        foreach (['Alice', $alice->cccd, $alice->phone, $alice->email] as $search) {
            $this->get('/admin/tenants?search='.urlencode($search))
                ->assertOk()
                ->assertSee($alice->full_name)
                ->assertDontSee($bob->cccd);
        }

        $this->get('/admin/tenants?search=missing-record')->assertOk()->assertSee('Chưa có khách thuê nào');
    }

    public function test_create_form_lists_only_unlinked_eligible_client_accounts(): void
    {
        $eligible = $this->user($this->clientRole, 'eligible@example.com', User::STATUS_PENDING);
        $linked = $this->user($this->clientRole, 'linked@example.com');
        $this->tenant($linked);
        $inactive = $this->user($this->clientRole, 'inactive@example.com', User::STATUS_INACTIVE);
        $auditorRole = Role::create(['role_name' => 'Auditor']);
        $auditor = $this->user($auditorRole, 'auditor@example.com');

        $this->actingAs($this->admin)->get('/admin/tenants/create')
            ->assertOk()
            ->assertSee('<option value="'.$eligible->id.'"', false)
            ->assertDontSee('<option value="'.$linked->id.'"', false)
            ->assertDontSee('<option value="'.$inactive->id.'"', false)
            ->assertDontSee('<option value="'.$this->admin->id.'"', false)
            ->assertDontSee('<option value="'.$auditor->id.'"', false)
            ->assertSee('name="gender"', false);
    }

    public function test_admin_can_create_tenant_and_link_account(): void
    {
        $client = $this->user($this->clientRole, 'new-client@example.com', User::STATUS_PENDING);

        $this->actingAs($this->admin)->post('/admin/tenants', $this->payload($client))
            ->assertRedirect('/admin/tenants')
            ->assertSessionHas('success');

        $tenant = Tenant::where('user_id', $client->id)->sole();
        $this->assertSame('Khách thuê mới', $tenant->full_name);
        $this->assertSame('female', $tenant->gender);
        $this->assertSame('079000000201', $tenant->cccd);
        $this->assertSame('0900000201', $tenant->phone);
        $this->assertSame($tenant->id, $client->fresh()->tenant->id);
    }

    public function test_create_validation_rejects_invalid_identity_dates_contact_and_account_links(): void
    {
        $linkedClient = $this->user($this->clientRole, 'linked@example.com');
        $this->tenant($linkedClient);
        $existing = $this->tenant($this->user($this->clientRole, 'existing@example.com'), [
            'cccd' => '079000000211',
            'phone' => '0900000211',
            'email' => 'existing-tenant@example.com',
        ]);
        $before = Tenant::count();

        $this->actingAs($this->admin)->from('/admin/tenants/create')->post('/admin/tenants', [
            'user_id' => $linkedClient->id,
            'full_name' => '',
            'date_of_birth' => now()->addDay()->toDateString(),
            'gender' => 'invalid',
            'cccd' => $existing->cccd,
            'phone' => $existing->phone,
            'email' => $existing->email,
            'address' => str_repeat('a', 501),
        ])->assertRedirect('/admin/tenants/create')
            ->assertSessionHasErrors([
                'user_id', 'full_name', 'date_of_birth', 'gender', 'cccd',
                'phone', 'email', 'address',
            ]);

        $this->assertSame($before, Tenant::count());
    }

    public function test_direct_request_cannot_link_admin_unsupported_inactive_or_same_client_twice(): void
    {
        $auditorRole = Role::create(['role_name' => 'Auditor']);
        $auditor = $this->user($auditorRole, 'auditor@example.com');
        $inactive = $this->user($this->clientRole, 'inactive@example.com', User::STATUS_INACTIVE);
        $eligible = $this->user($this->clientRole, 'eligible@example.com');
        $this->tenant($eligible);

        $this->actingAs($this->admin);
        foreach ([$this->admin, $auditor, $inactive, $eligible] as $index => $user) {
            $this->from('/admin/tenants/create')->post('/admin/tenants', $this->payload($user, [
                'cccd' => '0790000003'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'phone' => '09000003'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'email' => "candidate{$index}@example.com",
            ]))->assertRedirect('/admin/tenants/create')->assertSessionHasErrors('user_id');
        }

        $this->assertSame(1, Tenant::count());
    }

    public function test_concurrent_account_link_conflict_returns_validation_instead_of_server_error(): void
    {
        $client = $this->user($this->clientRole, 'race@example.com');
        $insertedByConcurrentRequest = false;
        Tenant::creating(function (Tenant $tenant) use (&$insertedByConcurrentRequest, $client): void {
            if ((int) $tenant->user_id !== (int) $client->id || $insertedByConcurrentRequest) {
                return;
            }

            $insertedByConcurrentRequest = true;
            DB::table('tenants')->insert([
                'user_id' => $tenant->user_id,
                'full_name' => 'Request đồng thời thắng',
                'cccd' => '079000000991',
                'phone' => '0900000991',
                'email' => 'race-winner@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->actingAs($this->admin)->from('/admin/tenants/create')->post('/admin/tenants', $this->payload($client))
            ->assertRedirect('/admin/tenants/create')
            ->assertSessionHasErrors('user_id');

        $this->assertSame(1, Tenant::where('user_id', $client->id)->count());
        $this->assertDatabaseHas('tenants', ['user_id' => $client->id, 'full_name' => 'Request đồng thời thắng']);
    }

    public function test_admin_can_view_and_update_tenant_while_preserving_contracts(): void
    {
        $client = $this->user($this->clientRole, 'view@example.com');
        $tenant = $this->tenant($client);
        [$room, $contract] = $this->contract($tenant);

        $this->actingAs($this->admin)->get("/admin/tenants/{$tenant->id}")
            ->assertOk()
            ->assertSee($tenant->full_name)
            ->assertSee($contract->contract_code)
            ->assertSee($room->room_code);

        $this->put("/admin/tenants/{$tenant->id}", $this->payload($client, [
            'full_name' => 'Tên đã sửa',
            'cccd' => $tenant->cccd,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
        ]))->assertRedirect('/admin/tenants')->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'full_name' => 'Tên đã sửa']);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'tenant_id' => $tenant->id]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_edit_can_keep_current_account_or_relink_to_another_eligible_account(): void
    {
        $current = $this->user($this->clientRole, 'current@example.com', User::STATUS_INACTIVE);
        $tenant = $this->tenant($current);
        $replacement = $this->user($this->clientRole, 'replacement@example.com', User::STATUS_PENDING);

        $this->actingAs($this->admin)->get("/admin/tenants/{$tenant->id}/edit")
            ->assertOk()
            ->assertSee($current->email)
            ->assertSee($replacement->email);

        $this->put("/admin/tenants/{$tenant->id}", $this->payload($replacement, [
            'cccd' => $tenant->cccd,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
        ]))->assertRedirect('/admin/tenants');

        $this->assertSame($replacement->id, $tenant->fresh()->user_id);
        $this->assertNull($current->fresh()->tenant);
        $this->assertSame($tenant->id, $replacement->fresh()->tenant->id);
    }

    public function test_tenant_without_contract_can_be_deleted_but_linked_user_is_preserved(): void
    {
        $client = $this->user($this->clientRole, 'delete@example.com');
        $tenant = $this->tenant($client);

        $this->actingAs($this->admin)->delete("/admin/tenants/{$tenant->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseHas('users', ['id' => $client->id]);
        $this->delete("/admin/tenants/{$tenant->id}")->assertNotFound();
    }

    public function test_tenant_with_any_contract_cannot_be_deleted_from_ui_or_direct_request(): void
    {
        $client = $this->user($this->clientRole, 'contract@example.com');
        $tenant = $this->tenant($client);
        [$room, $contract] = $this->contract($tenant, Contract::STATUS_TERMINATED);

        $this->actingAs($this->admin)->get('/admin/tenants')
            ->assertOk()
            ->assertDontSee('action="'.route('admin.tenants.destroy', $tenant).'"', false);

        $this->delete("/admin/tenants/{$tenant->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'user_id' => $client->id]);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_nonexistent_tenant_returns_not_found_without_changes(): void
    {
        $this->actingAs($this->admin)->get('/admin/tenants/999999')->assertNotFound();
        $this->get('/admin/tenants/999999/edit')->assertNotFound();
        $this->put('/admin/tenants/999999', [])->assertNotFound();
        $this->delete('/admin/tenants/999999')->assertNotFound();
        $this->assertDatabaseCount('tenants', 0);
    }

    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'full_name' => 'Khách thuê mới',
            'date_of_birth' => '1998-01-01',
            'gender' => 'female',
            'cccd' => '079000000201',
            'cccd_issue_date' => '2020-01-01',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => '0900000201',
            'email' => 'tenant-new@example.com',
            'address' => 'Địa chỉ kiểm thử',
        ], $overrides);
    }

    private function user(Role $role, string $email, string $status = User::STATUS_ACTIVE): User
    {
        return User::create([
            'name' => 'Tài khoản '.$email,
            'email' => $email,
            'phone' => '0999999999',
            'role_id' => $role->id,
            'password' => 'password',
            'status' => $status,
        ]);
    }

    private function tenant(User $user, array $overrides = []): Tenant
    {
        static $sequence = 0;
        $sequence++;

        return Tenant::create(array_merge([
            'user_id' => $user->id,
            'full_name' => 'Khách thuê '.$sequence,
            'date_of_birth' => '1998-01-01',
            'gender' => 'other',
            'cccd' => '079'.str_pad((string) $sequence, 9, '0', STR_PAD_LEFT),
            'cccd_issue_date' => '2020-01-01',
            'phone' => '090'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT),
            'email' => "tenant{$sequence}@example.com",
        ], $overrides));
    }

    private function contract(Tenant $tenant, string $status = Contract::STATUS_ACTIVE): array
    {
        $room = Room::create([
            'room_code' => 'TENANT-'.$tenant->id,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'status' => $status === Contract::STATUS_ACTIVE ? Room::STATUS_OCCUPIED : Room::STATUS_AVAILABLE,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'TENANT-CONTRACT-'.$tenant->id,
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => $status,
        ]);

        return [$room, $contract];
    }
}
