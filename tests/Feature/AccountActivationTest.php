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

    public function test_guest_cannot_access_any_activation_step(): void
    {
        $this->get('/activate-account')->assertRedirect('/login');
        $this->get('/activate-account/personal')->assertRedirect('/login');
        $this->post('/activate-account/personal', [])->assertRedirect('/login');
    }

    public function test_pending_client_is_shown_four_step_onboarding_after_login(): void
    {
        $user = $this->pendingClient();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Temporary@123',
        ])->assertRedirect(route('account.activation.show'));

        $this->get('/activate-account')
            ->assertOk()
            ->assertSee('Bước 1/4')
            ->assertSee('Thông tin cá nhân');
        $this->assertSame(User::STATUS_PENDING, $user->fresh()->status);
        $this->assertNull($user->tenant);
    }

    public function test_client_cannot_skip_required_onboarding_steps(): void
    {
        $user = $this->pendingClient();

        $this->actingAs($user)->get('/activate-account/password')
            ->assertRedirect(route('account.activation.step.show', 'personal'));
        $this->post('/activate-account/contact', [])->assertRedirect(route('account.activation.step.show', 'personal'));
        $this->assertSame(User::STATUS_PENDING, $user->fresh()->status);
    }

    public function test_each_step_validates_and_does_not_activate_early(): void
    {
        $user = $this->pendingClient();
        $this->actingAs($user);

        $this->from('/activate-account/personal')->post('/activate-account/personal', [
            'name' => '', 'date_of_birth' => now()->addDay()->toDateString(), 'gender' => 'invalid',
        ])->assertRedirect('/activate-account/personal')->assertSessionHasErrors(['name', 'date_of_birth', 'gender']);

        $this->post('/activate-account/personal', $this->personalPayload())
            ->assertRedirect('/activate-account/identity');
        $this->assertSame(User::STATUS_PENDING, $user->fresh()->status);
        $this->assertNull($user->tenant);

        $this->from('/activate-account/identity')->post('/activate-account/identity', [
            'cccd' => '123', 'cccd_issue_date' => '1990-01-01', 'cccd_issue_place' => '',
        ])->assertRedirect('/activate-account/identity')->assertSessionHasErrors(['cccd', 'cccd_issue_date', 'cccd_issue_place']);
    }

    public function test_final_password_step_activates_account_and_creates_complete_tenant_profile(): void
    {
        $user = $this->pendingClient('new-tenant@example.com');
        $this->actingAs($user);
        $this->completeProfileSteps();

        $this->get('/activate-account/password')->assertOk()->assertSee('Bước 4/4')->assertSee('Tạo mật khẩu mới');
        $this->post('/activate-account/password', $this->passwordPayload())
            ->assertRedirect('/dashboard')
            ->assertSessionHasNoErrors();

        $user->refresh();
        $tenant = $user->tenant;
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->activated_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertTrue(Hash::check('New-password-123!', $user->password));
        $this->assertNotNull($tenant);
        $this->assertSame('Khách kích hoạt', $tenant->full_name);
        $this->assertSame('079000000099', $tenant->cccd);
        $this->assertSame('female', $tenant->gender);
        $this->assertSame('Hà Nội', $tenant->cccd_issue_place);
        $this->assertSame('123 Đường Test', $tenant->address);
    }

    public function test_existing_tenant_is_updated_only_after_final_step(): void
    {
        $user = $this->pendingClient();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'full_name' => 'Tên hồ sơ cũ',
            'cccd' => '079000000010',
            'phone' => $user->phone,
        ]);
        $this->actingAs($user);
        $this->completeProfileSteps(['cccd' => $tenant->cccd]);

        $this->assertSame('Tên hồ sơ cũ', $tenant->fresh()->full_name);
        $this->post('/activate-account/password', $this->passwordPayload())->assertRedirect('/dashboard');
        $this->assertSame('Khách kích hoạt', $tenant->fresh()->full_name);
        $this->assertSame($user->id, $tenant->fresh()->user_id);
    }

    public function test_final_step_rejects_temporary_or_weak_password(): void
    {
        $user = $this->pendingClient();
        $this->actingAs($user);
        $this->completeProfileSteps();

        $this->from('/activate-account/password')->post('/activate-account/password', [
            'password' => 'weakpassword', 'password_confirmation' => 'weakpassword',
        ])->assertRedirect('/activate-account/password')->assertSessionHasErrors('password');

        $this->post('/activate-account/password', [
            'password' => 'Temporary@123', 'password_confirmation' => 'Temporary@123',
        ])->assertSessionHasErrors('password');
        $this->assertSame(User::STATUS_PENDING, $user->fresh()->status);
    }

    public function test_activation_cannot_be_repeated_after_success(): void
    {
        $user = $this->pendingClient();
        $this->actingAs($user);
        $this->completeProfileSteps();
        $this->post('/activate-account/password', $this->passwordPayload())->assertRedirect('/dashboard');

        $this->get('/activate-account')->assertRedirect('/dashboard');
        $this->post('/activate-account/personal', $this->personalPayload())->assertForbidden();
    }

    public function test_only_pending_client_role_can_use_onboarding(): void
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Pending Admin', 'email' => 'pending-admin@example.com',
            'role_id' => $adminRole->id, 'password' => 'Temporary@123',
            'status' => User::STATUS_PENDING, 'must_change_password' => true,
        ]);

        $this->actingAs($admin)->get('/activate-account')->assertForbidden();
        $this->post('/activate-account/personal', [])->assertForbidden();
    }

    private function completeProfileSteps(array $identityOverrides = []): void
    {
        $this->post('/activate-account/personal', $this->personalPayload())->assertRedirect('/activate-account/identity');
        $this->post('/activate-account/identity', array_merge([
            'cccd' => '079000000099',
            'cccd_issue_date' => '2021-06-01',
            'cccd_issue_place' => 'Hà Nội',
        ], $identityOverrides))->assertRedirect('/activate-account/contact');
        $this->post('/activate-account/contact', [
            'phone' => '0911111111',
            'address' => '123 Đường Test',
            'accept_terms' => '1',
        ])->assertRedirect('/activate-account/password');
    }

    private function personalPayload(): array
    {
        return ['name' => 'Khách kích hoạt', 'date_of_birth' => '1995-05-20', 'gender' => 'female'];
    }

    private function passwordPayload(): array
    {
        return ['password' => 'New-password-123!', 'password_confirmation' => 'New-password-123!'];
    }

    private function pendingClient(string $email = 'pending@example.com'): User
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);

        return User::create([
            'name' => 'Khách thuê', 'email' => $email, 'phone' => '0900000000',
            'role_id' => $role->id, 'password' => 'Temporary@123',
            'status' => User::STATUS_PENDING, 'must_change_password' => true,
        ]);
    }
}
