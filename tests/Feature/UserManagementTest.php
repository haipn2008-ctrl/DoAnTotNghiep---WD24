<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $clientRole;

    private Role $unsupportedRole;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->adminRole = Role::create(['id' => 10, 'role_name' => 'Admin']);
        $this->clientRole = Role::create(['id' => 20, 'role_name' => 'User']);
        $this->unsupportedRole = Role::create(['id' => 30, 'role_name' => 'Auditor']);
        $this->admin = $this->user($this->adminRole, 'manager@example.com', 'Quản trị chính');
    }

    public function test_only_admin_can_access_user_management_including_direct_mutation_requests(): void
    {
        $target = $this->user($this->clientRole, 'target@example.com', 'Tài khoản đích');

        $this->get('/admin/users')->assertRedirect('/login');
        $this->post('/admin/users', [])->assertRedirect('/login');
        $this->put("/admin/users/{$target->id}", [])->assertRedirect('/login');
        $this->delete("/admin/users/{$target->id}")->assertRedirect('/login');

        $client = $this->user($this->clientRole, 'client@example.com', 'Khách thuê');
        $this->actingAs($client)->get('/admin/users')->assertForbidden();
        $this->post('/admin/users', [])->assertForbidden();
        $this->put("/admin/users/{$target->id}", [])->assertForbidden();
        $this->delete("/admin/users/{$target->id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_admin_can_list_and_search_accounts_by_name_or_email(): void
    {
        $alice = $this->user($this->clientRole, 'alice.unique@example.com', 'Alice Unique');
        $bob = $this->user($this->clientRole, 'bob@example.com', 'Bob Separate');

        $this->actingAs($this->admin)->get('/admin/users')
            ->assertOk()
            ->assertViewHas('users')
            ->assertSee($alice->email)
            ->assertSee($bob->email);

        $this->get('/admin/users?search=Alice')
            ->assertOk()
            ->assertSee($alice->email)
            ->assertDontSee($bob->email);

        $this->get('/admin/users?search=bob%40example.com')
            ->assertOk()
            ->assertSee($bob->name)
            ->assertDontSee($alice->email);

        $this->get('/admin/users?search=does-not-exist')
            ->assertOk()
            ->assertSee('Chưa có tài khoản nào');
    }

    public function test_create_form_only_exposes_supported_roles(): void
    {
        $this->actingAs($this->admin)->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Admin')
            ->assertSee('User')
            ->assertDontSee('Auditor')
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_admin_can_create_admin_and_client_with_correct_initial_lifecycle(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', $this->createPayload([
            'email' => 'new-admin@example.com',
            'role_id' => $this->adminRole->id,
        ]))->assertRedirect('/admin/users')->assertSessionHas('success');

        $newAdmin = User::where('email', 'new-admin@example.com')->sole();
        $this->assertSame(User::STATUS_ACTIVE, $newAdmin->status);
        $this->assertFalse($newAdmin->must_change_password);
        $this->assertNotNull($newAdmin->activated_at);
        $this->assertTrue(Hash::check('strong-password', $newAdmin->password));

        $this->post('/admin/users', $this->createPayload([
            'email' => 'new-client@example.com',
            'role_id' => $this->clientRole->id,
        ]))->assertRedirect('/admin/users')->assertSessionHas('success');

        $newClient = User::where('email', 'new-client@example.com')->sole();
        $this->assertSame(User::STATUS_PENDING, $newClient->status);
        $this->assertTrue($newClient->must_change_password);
        $this->assertNull($newClient->activated_at);
        $this->assertTrue(Hash::check('strong-password', $newClient->password));
    }

    public function test_create_validation_rejects_missing_duplicate_weak_or_unsupported_data(): void
    {
        $before = User::count();

        $this->actingAs($this->admin)->from('/admin/users/create')->post('/admin/users', [
            'name' => '',
            'email' => $this->admin->email,
            'phone' => str_repeat('1', 21),
            'role_id' => $this->unsupportedRole->id,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors(['name', 'email', 'phone', 'role_id', 'password']);

        $this->assertSame($before, User::count());
        $this->assertTrue(Hash::check('password', $this->admin->fresh()->password));
    }

    public function test_concurrent_duplicate_email_is_returned_as_validation_instead_of_server_error(): void
    {
        $insertedByConcurrentRequest = false;
        User::creating(function (User $user) use (&$insertedByConcurrentRequest): void {
            if ($user->email !== 'race@example.com' || $insertedByConcurrentRequest) {
                return;
            }

            $insertedByConcurrentRequest = true;
            DB::table('users')->insert([
                'name' => 'Request đồng thời thắng',
                'email' => $user->email,
                'phone' => '0900000999',
                'password' => Hash::make('other-password'),
                'role_id' => $this->clientRole->id,
                'status' => User::STATUS_PENDING,
                'must_change_password' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->actingAs($this->admin)->from('/admin/users/create')->post('/admin/users', $this->createPayload([
            'email' => 'race@example.com',
        ]))->assertRedirect('/admin/users/create')->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'race@example.com')->count());
        $this->assertDatabaseHas('users', [
            'email' => 'race@example.com',
            'name' => 'Request đồng thời thắng',
        ]);
    }

    public function test_admin_can_update_profile_status_and_optionally_password(): void
    {
        $client = $this->user($this->clientRole, 'edit@example.com', 'Tên cũ', User::STATUS_PENDING);
        $oldPassword = $client->password;

        $this->actingAs($this->admin)->put("/admin/users/{$client->id}", [
            'name' => 'Tên mới',
            'email' => 'updated@example.com',
            'phone' => '0911111111',
            'role_id' => $this->clientRole->id,
            'status' => User::STATUS_ACTIVE,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect('/admin/users')->assertSessionHas('success');

        $client->refresh();
        $this->assertSame('Tên mới', $client->name);
        $this->assertSame('updated@example.com', $client->email);
        $this->assertSame($oldPassword, $client->password);
        $this->assertSame(User::STATUS_ACTIVE, $client->status);
        $this->assertNotNull($client->activated_at);
        $this->assertFalse($client->must_change_password);

        $this->put("/admin/users/{$client->id}", [
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'role_id' => $client->role_id,
            'status' => User::STATUS_PENDING,
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirect('/admin/users');

        $client->refresh();
        $this->assertSame(User::STATUS_PENDING, $client->status);
        $this->assertNull($client->activated_at);
        $this->assertTrue($client->must_change_password);
        $this->assertTrue(Hash::check('replacement-password', $client->password));
    }

    public function test_update_rejects_duplicate_email_invalid_status_and_missing_record(): void
    {
        $client = $this->user($this->clientRole, 'edit@example.com', 'Tên cũ');

        $this->actingAs($this->admin)->from("/admin/users/{$client->id}/edit")->put("/admin/users/{$client->id}", [
            'name' => 'Không được lưu',
            'email' => $this->admin->email,
            'role_id' => $this->clientRole->id,
            'status' => 'unknown',
        ])->assertRedirect("/admin/users/{$client->id}/edit")
            ->assertSessionHasErrors(['email', 'status']);

        $this->assertSame('Tên cũ', $client->fresh()->name);
        $this->get('/admin/users/999999/edit')->assertNotFound();
        $this->put('/admin/users/999999', [])->assertNotFound();
        $this->delete('/admin/users/999999')->assertNotFound();
    }

    public function test_changing_role_resets_lifecycle_without_changing_existing_password(): void
    {
        $client = $this->user($this->clientRole, 'promote@example.com', 'Sắp thành admin', User::STATUS_PENDING);
        $clientPassword = $client->password;

        $this->actingAs($this->admin)->put("/admin/users/{$client->id}", [
            'name' => $client->name,
            'email' => $client->email,
            'role_id' => $this->adminRole->id,
            'status' => User::STATUS_PENDING,
        ])->assertRedirect('/admin/users');

        $client->refresh();
        $this->assertSame($this->adminRole->id, $client->role_id);
        $this->assertSame(User::STATUS_ACTIVE, $client->status);
        $this->assertNotNull($client->activated_at);
        $this->assertFalse($client->must_change_password);
        $this->assertSame($clientPassword, $client->password);

        $otherAdmin = $this->user($this->adminRole, 'demote@example.com', 'Sắp thành client');
        $adminPassword = $otherAdmin->password;
        $this->put("/admin/users/{$otherAdmin->id}", [
            'name' => $otherAdmin->name,
            'email' => $otherAdmin->email,
            'role_id' => $this->clientRole->id,
            'status' => User::STATUS_ACTIVE,
        ])->assertRedirect('/admin/users');

        $otherAdmin->refresh();
        $this->assertSame($this->clientRole->id, $otherAdmin->role_id);
        $this->assertSame(User::STATUS_PENDING, $otherAdmin->status);
        $this->assertNull($otherAdmin->activated_at);
        $this->assertTrue($otherAdmin->must_change_password);
        $this->assertSame($adminPassword, $otherAdmin->password);
    }

    public function test_admin_cannot_change_own_role_status_or_delete_self_from_ui_or_backend(): void
    {
        $other = $this->user($this->clientRole, 'other@example.com', 'Người khác');

        $this->actingAs($this->admin)->get('/admin/users')
            ->assertOk()
            ->assertDontSee('action="'.route('admin.users.destroy', $this->admin).'"', false)
            ->assertSee('action="'.route('admin.users.destroy', $other).'"', false);

        $this->get("/admin/users/{$this->admin->id}/edit")
            ->assertOk()
            ->assertSee('Không thể tự thay đổi vai trò hoặc trạng thái tài khoản.')
            ->assertSee('name="role_id" value="'.$this->admin->role_id.'"', false)
            ->assertSee('name="status" value="active"', false);

        $this->from("/admin/users/{$this->admin->id}/edit")->put("/admin/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->clientRole->id,
            'status' => User::STATUS_LOCKED,
        ])->assertRedirect("/admin/users/{$this->admin->id}/edit")
            ->assertSessionHasErrors('status');

        $this->delete("/admin/users/{$this->admin->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');

        $this->admin->refresh();
        $this->assertSame($this->adminRole->id, $this->admin->role_id);
        $this->assertSame(User::STATUS_ACTIVE, $this->admin->status);
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_can_delete_another_account_and_repeated_delete_is_not_found(): void
    {
        $target = $this->user($this->clientRole, 'delete@example.com', 'Cần xóa');

        $this->actingAs($this->admin)->delete("/admin/users/{$target->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);

        $this->delete("/admin/users/{$target->id}")->assertNotFound();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_account_with_open_contract_cannot_be_deleted_or_orphan_related_data(): void
    {
        $target = $this->user($this->clientRole, 'renting@example.com', 'Đang thuê');
        $tenant = Tenant::create([
            'user_id' => $target->id,
            'full_name' => $target->name,
            'cccd' => '079000000333',
            'phone' => '0900000333',
        ]);
        $room = Room::create([
            'room_code' => 'USR-ROOM',
            'floor' => 3,
            'price' => 3000000,
            'area' => 25,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'USR-CONTRACT',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'start_date' => '2026-08-01',
            'end_date' => '2027-08-01',
            'status' => Contract::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin)->delete("/admin/users/{$target->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'user_id' => $target->id]);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'status' => Contract::STATUS_ACTIVE]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => Room::STATUS_OCCUPIED]);

        $contract->forceFill(['status' => Contract::STATUS_SETTLING])->save();
        $room->update(['status' => Room::STATUS_AVAILABLE]);
        $invoice = Invoice::create([
            'invoice_code' => 'USR-INVOICE',
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'month' => 8,
            'year' => 2026,
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'room_fee' => 3000000,
            'total_amount' => 3000000,
            'status' => Invoice::STATUS_UNPAID,
        ]);

        $this->delete("/admin/users/{$target->id}")
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::STATUS_UNPAID]);

        $invoice->update(['status' => Invoice::STATUS_PAID]);
        $this->delete("/admin/users/{$target->id}")->assertSessionHas('error');
        $contract->forceFill([
            'status' => Contract::STATUS_COMPLETED,
            'actual_move_out_at' => now(),
            'completed_at' => now(),
            'deposit_resolution' => Contract::DEPOSIT_NOT_REQUIRED,
        ])->save();
        $this->delete("/admin/users/{$target->id}")->assertRedirect('/admin/users')->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'user_id' => null]);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_manual_client_status_must_match_contract_and_debt_state(): void
    {
        $target = $this->user($this->clientRole, 'lifecycle@example.com', 'Theo vòng đời');
        $tenant = Tenant::create([
            'user_id' => $target->id,
            'full_name' => $target->name,
            'cccd' => '079000000444',
            'phone' => '0900000444',
        ]);
        $room = Room::create([
            'room_code' => 'USR-LIFECYCLE',
            'floor' => 4,
            'price' => 3000000,
            'area' => 25,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'USR-LIFECYCLE',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'start_date' => '2026-08-01',
            'end_date' => '2027-08-01',
            'status' => Contract::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin)->get("/admin/users/{$target->id}/edit")
            ->assertOk()
            ->assertSee('value="active"', false)
            ->assertSee('value="locked"', false)
            ->assertDontSee('value="settling"', false)
            ->assertDontSee('value="inactive"', false);

        $this->from("/admin/users/{$target->id}/edit")->put("/admin/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $target->role_id,
            'status' => User::STATUS_INACTIVE,
        ])->assertRedirect("/admin/users/{$target->id}/edit")->assertSessionHasErrors('status');
        $this->assertSame(User::STATUS_ACTIVE, $target->fresh()->status);

        $contract->forceFill(['status' => Contract::STATUS_SETTLING])->save();
        $invoice = Invoice::create([
            'invoice_code' => 'USR-LIFECYCLE-DEBT',
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'month' => 9,
            'year' => 2026,
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-09-10',
            'room_fee' => 3000000,
            'total_amount' => 3000000,
            'status' => Invoice::STATUS_UNPAID,
        ]);

        $this->put("/admin/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $target->role_id,
            'status' => User::STATUS_SETTLING,
        ])->assertRedirect('/admin/users');
        $this->assertSame(User::STATUS_SETTLING, $target->fresh()->status);

        $invoice->update(['status' => Invoice::STATUS_PAID]);
        $this->from("/admin/users/{$target->id}/edit")->put("/admin/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $target->role_id,
            'status' => User::STATUS_INACTIVE,
        ])->assertRedirect("/admin/users/{$target->id}/edit")->assertSessionHasErrors('status');
        $this->assertSame(User::STATUS_SETTLING, $target->fresh()->status);

        $contract->forceFill(['status' => Contract::STATUS_COMPLETED, 'completed_at' => now()])->save();
        $this->put("/admin/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $target->role_id,
            'status' => User::STATUS_INACTIVE,
        ])->assertRedirect('/admin/users');
        $this->assertSame(User::STATUS_INACTIVE, $target->fresh()->status);
    }

    private function createPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tài khoản mới',
            'email' => 'new@example.com',
            'phone' => '0900000000',
            'role_id' => $this->clientRole->id,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ], $overrides);
    }

    private function user(Role $role, string $email, string $name, string $status = User::STATUS_ACTIVE): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'phone' => '0900000000',
            'role_id' => $role->id,
            'password' => 'password',
            'status' => $status,
            'activated_at' => $status === User::STATUS_PENDING ? null : now(),
            'must_change_password' => $status === User::STATUS_PENDING,
        ]);
    }
}
