<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->post('/admin/tenants', [])->assertStatus(405);
        $this->put("/admin/tenants/{$tenant->id}", [])->assertRedirect('/login');
        $this->delete("/admin/tenants/{$tenant->id}")->assertRedirect('/login');

        $client = $this->user($this->clientRole, 'client@example.com');
        $this->actingAs($client)->get('/admin/tenants')->assertForbidden();
        $this->post('/admin/tenants', [])->assertStatus(405);
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

        $this->get('/admin/tenants?search=missing-record')->assertOk()->assertSee('Không tìm thấy khách thuê');
    }

    public function test_tenant_list_has_direct_export_and_live_filter_controls_without_search_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tenants.index'));

        $response->assertOk()
            ->assertSee('data-tenant-filter', false)
            ->assertSee('data-tenant-search', false)
            ->assertSee('data-tenant-status', false)
            ->assertSee('data-tenant-export', false)
            ->assertSee('href="'.route('admin.tenants.export').'"', false)
            ->assertDontSee('>Tìm</button>', false);

        $this->get('/admin/tenants/export/download')->assertNotFound();
    }

    public function test_tenant_list_only_labels_the_representative(): void
    {
        $representative = $this->tenant($this->user($this->clientRole, 'representative@example.com'), [
            'full_name' => 'Người đại diện kiểm thử',
        ]);
        [, $contract] = $this->contract($representative);
        $member = Tenant::create([
            'full_name' => 'Người thuê cùng phòng kiểm thử',
            'date_of_birth' => '1997-02-02',
            'gender' => 'female',
            'cccd' => '079000009999',
            'cccd_issue_date' => '2020-01-01',
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => '0900099999',
            'email' => 'member-without-account@example.com',
            'address' => 'Địa chỉ kiểm thử',
        ]);
        ContractTenant::create([
            'contract_id' => $contract->id,
            'tenant_id' => $member->id,
            'role' => ContractTenant::ROLE_TENANT,
            'full_name' => $member->full_name,
            'date_of_birth' => $member->date_of_birth,
            'identity_number' => $member->cccd,
            'phone' => $member->phone,
            'address' => $member->address,
            'status' => ContractTenant::STATUS_CHECKED_IN,
        ]);

        $this->actingAs($this->admin)->get(route('admin.tenants.index'))
            ->assertOk()
            ->assertSee('Người đại diện kiểm thử')
            ->assertSee('Người thuê đại diện · Có tài khoản')
            ->assertSee('Người thuê cùng phòng kiểm thử')
            ->assertDontSee('Người thuê cùng phòng</span>', false)
            ->assertDontSee('Chưa có tài khoản')
            ->assertDontSee('Dữ liệu cũ thiếu tài khoản')
            ->assertDontSee('Có tài khoản portal');
    }

    public function test_ajax_search_and_rental_status_filters_return_only_matching_rows(): void
    {
        $renting = $this->tenant($this->user($this->clientRole, 'renting@example.com'), [
            'full_name' => 'Nguyễn Đang Thuê',
            'cccd' => '079000000881',
            'phone' => '0900000881',
            'email' => 'renting.tenant@example.com',
        ]);
        $available = $this->tenant($this->user($this->clientRole, 'available@example.com'), [
            'full_name' => 'Trần Chưa Thuê',
            'cccd' => '079000000882',
            'phone' => '0900000882',
            'email' => 'available.tenant@example.com',
        ]);
        $this->contract($renting);

        $this->actingAs($this->admin)
            ->get(route('admin.tenants.index', ['search' => 'Nguyễn', 'status' => 'renting']), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertSee($renting->cccd)
            ->assertDontSee($available->cccd)
            ->assertDontSee('data-tenant-filter', false);

        $this->get(route('admin.tenants.index', ['status' => 'not_renting']), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()
            ->assertSee($available->cccd)
            ->assertDontSee($renting->cccd);

        $this->get(route('admin.tenants.index', ['status' => 'unknown']))
            ->assertSessionHasErrors('status');
    }

    public function test_manual_tenant_creation_routes_are_disabled(): void
    {
        $before = Tenant::count();

        $this->actingAs($this->admin)->get('/admin/tenants/create')->assertNotFound();
        $this->post('/admin/tenants', [
            'full_name' => 'Khách tạo thủ công',
            'cccd' => '079000000777',
            'phone' => '0900000777',
        ])->assertStatus(405);

        $this->get('/admin/tenants')->assertOk()->assertDontSee('Thêm khách thuê');
        $this->assertSame($before, Tenant::count());
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

    public function test_edit_shows_linked_account_but_cannot_relink_it(): void
    {
        $current = $this->user($this->clientRole, 'current@example.com', User::STATUS_INACTIVE);
        $tenant = $this->tenant($current);
        $replacement = $this->user($this->clientRole, 'replacement@example.com', User::STATUS_PENDING);

        $this->actingAs($this->admin)->get("/admin/tenants/{$tenant->id}/edit")
            ->assertOk()
            ->assertSee($current->email)
            ->assertDontSee($replacement->email)
            ->assertDontSee('name="user_id"', false);

        $this->from("/admin/tenants/{$tenant->id}/edit")->put("/admin/tenants/{$tenant->id}", array_merge($this->payload($replacement, [
            'cccd' => $tenant->cccd,
            'phone' => $tenant->phone,
            'email' => $tenant->email,
        ]), ['user_id' => $replacement->id]))->assertRedirect("/admin/tenants/{$tenant->id}/edit")
            ->assertSessionHasErrors('user_id');

        $this->assertSame($current->id, $tenant->fresh()->user_id);
        $this->assertSame($tenant->id, $current->fresh()->tenant->id);
        $this->assertNull($replacement->fresh()->tenant);
    }

    public function test_tenant_without_contract_is_archived_and_linked_user_is_deactivated(): void
    {
        $client = $this->user($this->clientRole, 'delete@example.com');
        $tenant = $this->tenant($client);

        $this->actingAs($this->admin)->delete("/admin/tenants/{$tenant->id}", [
            'archive_reason' => 'Hồ sơ được tạo nhưng không còn sử dụng.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => Tenant::STATUS_ARCHIVED]);
        $this->assertDatabaseHas('users', ['id' => $client->id, 'status' => User::STATUS_INACTIVE]);
        $this->delete("/admin/tenants/{$tenant->id}", [
            'archive_reason' => 'Thử lưu trữ hồ sơ thêm lần nữa.',
        ])->assertSessionHas('error');
    }

    public function test_tenant_with_historical_contract_can_be_archived_without_losing_history(): void
    {
        $client = $this->user($this->clientRole, 'contract@example.com');
        $tenant = $this->tenant($client);
        [$room, $contract] = $this->contract($tenant, Contract::STATUS_TERMINATED);

        $this->actingAs($this->admin)->get('/admin/tenants')
            ->assertOk()
            ->assertSee('action="'.route('admin.tenants.destroy', $tenant).'"', false);

        $this->delete("/admin/tenants/{$tenant->id}", [
            'archive_reason' => 'Khách đã kết thúc hợp đồng và rời phòng.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'user_id' => $client->id, 'status' => Tenant::STATUS_ARCHIVED]);
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
            'cccd_issue_place' => 'Cục Cảnh sát QLHC về TTXH',
            'phone' => '090'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT),
            'email' => "tenant{$sequence}@example.com",
            'address' => 'Địa chỉ kiểm thử',
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
