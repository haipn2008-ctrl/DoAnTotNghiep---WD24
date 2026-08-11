<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_or_submit_activation(): void
    {
        $this->get('/activate-account')->assertRedirect('/login');
        $this->post('/activate-account', [])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_pending_client_must_accept_terms_and_change_password_after_login(): void
    {
        $role = Role::create(['role_name' => 'User']);
        $user = User::create([
            'name' => 'Khách thuê',
            'email' => 'pending@example.com',
            'phone' => '0900000000',
            'role_id' => $role->id,
            'password' => 'temporary-password',
            'status' => User::STATUS_PENDING,
            'must_change_password' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'full_name' => 'Khách thuê cũ',
            'cccd' => '079000000010',
            'phone' => '0900000000',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'temporary-password',
        ])->assertRedirect(route('account.activation.show'));

        $this->get('/activate-account')->assertOk();

        $user->refresh();
        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertTrue($user->must_change_password);
        $this->assertNull($user->activated_at);
        $this->assertNull($user->terms_accepted_at);
        $this->assertTrue(Hash::check('temporary-password', $user->password));
        $this->assertSame('Khách thuê cũ', $tenant->fresh()->full_name);
        $this->assertSame('0900000000', $tenant->fresh()->phone);
        $this->assertAuthenticatedAs($user);

        $this->post('/activate-account', [
            'name' => 'Khách thuê mới',
            'phone' => '0911111111',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
            'accept_terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->activated_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertSame('Khách thuê mới', $tenant->fresh()->full_name);
        $this->assertSame('0911111111', $tenant->fresh()->phone);
    }

    public function test_activation_validation_failure_changes_nothing(): void
    {
        $user = $this->pendingClient();
        $originalPassword = $user->password;

        $this->actingAs($user)->from('/activate-account')->post('/activate-account', [
            'name' => '',
            'phone' => '',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertRedirect('/activate-account')
            ->assertSessionHasErrors(['name', 'phone', 'password', 'accept_terms']);

        $user->refresh();
        $this->assertSame(User::STATUS_PENDING, $user->status);
        $this->assertTrue($user->must_change_password);
        $this->assertNull($user->activated_at);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_activation_automatically_creates_basic_tenant_profile_without_occupants(): void
    {
        $user = $this->pendingClient('new-tenant@example.com');
        $this->assertNull($user->tenant);

        $this->actingAs($user)->post('/activate-account', [
            'name' => 'Khách vừa kích hoạt',
            'phone' => '0912345678',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
            'accept_terms' => '1',
        ])->assertRedirect('/dashboard')->assertSessionHasNoErrors();

        $tenant = $user->fresh()->tenant;
        $this->assertNotNull($tenant);
        $this->assertSame('Khách vừa kích hoạt', $tenant->full_name);
        $this->assertSame('0912345678', $tenant->phone);
        $this->assertSame('new-tenant@example.com', $tenant->email);
        $this->assertNull($tenant->cccd);
        $this->assertDatabaseCount('contract_occupants', 0);
    }

    public function test_activation_rejects_reusing_temporary_password(): void
    {
        $user = $this->pendingClient();

        $this->actingAs($user)->from('/activate-account')->post('/activate-account', [
            'name' => 'Khách thuê',
            'phone' => '0911111111',
            'password' => 'temporary-password',
            'password_confirmation' => 'temporary-password',
            'accept_terms' => '1',
        ])->assertRedirect('/activate-account')->assertSessionHasErrors('password');

        $this->assertSame(User::STATUS_PENDING, $user->fresh()->status);
        $this->assertTrue(Hash::check('temporary-password', $user->fresh()->password));
    }

    public function test_activation_cannot_be_repeated_after_success(): void
    {
        $user = $this->pendingClient();
        $payload = [
            'name' => 'Tên kích hoạt',
            'phone' => '0911111111',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
            'accept_terms' => '1',
        ];

        $this->actingAs($user)->post('/activate-account', $payload)->assertRedirect('/dashboard');
        $activatedAt = $user->fresh()->activated_at;
        $password = $user->fresh()->password;

        $this->post('/activate-account', $payload + ['name' => 'Tên bị ghi đè'])->assertForbidden();

        $user->refresh();
        $this->assertSame('Tên kích hoạt', $user->name);
        $this->assertTrue($user->activated_at->equalTo($activatedAt));
        $this->assertSame($password, $user->password);
    }

    public function test_only_pending_client_role_can_activate(): void
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Pending Admin',
            'email' => 'pending-admin@example.com',
            'role_id' => $adminRole->id,
            'password' => 'temporary-password',
            'status' => User::STATUS_PENDING,
            'must_change_password' => true,
        ]);

        $this->actingAs($admin)->get('/activate-account')->assertForbidden();
        $this->post('/activate-account', [])->assertForbidden();
        $this->assertSame(User::STATUS_PENDING, $admin->fresh()->status);
    }

    public function test_non_pending_accounts_cannot_submit_activation(): void
    {
        foreach ([User::STATUS_ACTIVE, User::STATUS_SETTLING, User::STATUS_LOCKED, User::STATUS_INACTIVE] as $index => $status) {
            $user = $this->pendingClient("state{$index}@example.com");
            $user->update(['status' => $status]);

            $response = $this->actingAs($user)->get('/activate-account');
            $status === User::STATUS_ACTIVE ? $response->assertRedirect('/dashboard') : $response->assertForbidden();
            $this->post('/activate-account', [])->assertForbidden();
            $this->assertSame($status, $user->fresh()->status);
        }
    }

    public function test_locked_account_cannot_log_in(): void
    {
        $role = Role::create(['role_name' => 'User']);
        $user = User::create([
            'name' => 'Locked User',
            'email' => 'locked@example.com',
            'role_id' => $role->id,
            'password' => 'secret-password',
            'status' => User::STATUS_LOCKED,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function pendingClient(string $email = 'pending@example.com'): User
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);

        return User::create([
            'name' => 'Khách thuê',
            'email' => $email,
            'phone' => '0900000000',
            'role_id' => $role->id,
            'password' => 'temporary-password',
            'status' => User::STATUS_PENDING,
            'must_change_password' => true,
        ]);
    }
}
