<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        RateLimiter::clear('admin@example.com|127.0.0.1');
        RateLimiter::clear('client@example.com|127.0.0.1');
        RateLimiter::clear('missing@example.com|127.0.0.1');

        $this->adminRole = Role::create(['role_name' => 'Admin']);
        $this->clientRole = Role::create(['role_name' => 'User']);
    }

    public function test_guest_can_view_login_form_but_authenticated_user_is_redirected(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);

        $this->actingAs($this->user($this->adminRole, 'admin@example.com'))
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_login_requires_a_valid_email_and_password_without_authenticating(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);

        $this->from('/login')->post('/login', [
            'email' => 'not-an-email',
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors(['email']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_wrong_credentials_are_rejected_without_changing_login_audit_data(): void
    {
        $user = $this->user($this->adminRole, 'admin@example.com');

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_active_admin_and_client_can_login_and_are_routed_to_their_own_portals(): void
    {
        $admin = $this->user($this->adminRole, 'admin@example.com');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->get('/dashboard')->assertRedirect('/admin');

        $this->post('/logout')->assertRedirect('/login');

        $client = $this->user($this->clientRole, 'client@example.com');
        $this->post('/login', ['email' => $client->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($client);
        $this->assertNotNull($client->fresh()->last_login_at);
        $this->get('/dashboard')->assertRedirect('/client');
    }

    public function test_stale_intended_url_cannot_redirect_a_user_into_another_roles_portal(): void
    {
        $admin = $this->user($this->adminRole, 'admin@example.com');

        $this->withSession(['url.intended' => '/client'])
            ->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/dashboard')
            ->assertSessionMissing('url.intended');
        $this->get('/dashboard')->assertRedirect('/admin');

        $this->post('/logout');

        $client = $this->user($this->clientRole, 'client@example.com');
        $this->withSession(['url.intended' => '/admin'])
            ->post('/login', ['email' => $client->email, 'password' => 'password'])
            ->assertRedirect('/dashboard')
            ->assertSessionMissing('url.intended');
        $this->get('/dashboard')->assertRedirect('/client');
    }

    public function test_pending_client_is_sent_to_activation_and_remains_pending_after_login(): void
    {
        $user = $this->user($this->clientRole, 'client@example.com', User::STATUS_PENDING);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/activate-account');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertSame(User::STATUS_PENDING, $user->fresh()->status);
        $this->assertNull($user->fresh()->activated_at);

        $this->get('/client')->assertRedirect('/activate-account');
    }

    public function test_settling_account_can_login_and_access_only_the_settlement_portal(): void
    {
        $user = $this->user($this->clientRole, 'client@example.com', User::STATUS_SETTLING);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/client/settlement');
        $this->get('/client/settlement')->assertSuccessful();
        $this->get('/client')->assertRedirect('/client/settlement');
        $this->get('/client/room')->assertRedirect('/client/invoices');
        $this->assertAuthenticatedAs($user);
    }

    public function test_locked_and_inactive_accounts_are_rejected_without_audit_changes(): void
    {
        foreach ([User::STATUS_LOCKED, User::STATUS_INACTIVE] as $index => $status) {
            $user = $this->user($this->clientRole, "client{$index}@example.com", $status);

            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect('/login')->assertSessionHasErrors('email');

            $this->assertGuest();
            $this->assertNull($user->fresh()->last_login_at);
        }
    }

    public function test_account_disabled_during_a_session_is_logged_out_on_next_protected_request(): void
    {
        $user = $this->user($this->clientRole, 'client@example.com');
        $this->actingAs($user);
        $user->update(['status' => User::STATUS_LOCKED]);

        $this->get('/client')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_protected_routes_and_cannot_use_get_to_logout(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/client')->assertRedirect('/login');
        $this->get('/logout')->assertMethodNotAllowed();
        $this->assertGuest();
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = $this->user($this->adminRole, 'admin@example.com');

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_direct_cross_role_requests_are_forbidden_without_changing_authentication(): void
    {
        $client = $this->user($this->clientRole, 'client@example.com');
        $this->actingAs($client)->get('/admin/users')->assertForbidden();
        $this->assertAuthenticatedAs($client);

        $admin = $this->user($this->adminRole, 'admin@example.com');
        $this->actingAs($admin)->get('/client/account')->assertForbidden();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_unknown_role_is_forbidden_instead_of_entering_a_redirect_loop(): void
    {
        $unknownRole = Role::create(['role_name' => 'Auditor']);
        $user = $this->user($unknownRole, 'client@example.com');

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/client')->assertForbidden();
    }

    public function test_repeated_failed_logins_are_rate_limited_and_success_clears_the_counter(): void
    {
        $user = $this->user($this->adminRole, 'admin@example.com');

        foreach (range(1, 5) as $attempt) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertRedirect('/login')->assertSessionHasErrors('email');
        }

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);

        RateLimiter::clear('admin@example.com|127.0.0.1');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, RateLimiter::attempts('admin@example.com|127.0.0.1'));
    }

    private function user(Role $role, string $email, string $status = User::STATUS_ACTIVE): User
    {
        return User::create([
            'name' => 'Người dùng kiểm thử',
            'email' => $email,
            'phone' => '0900000000',
            'role_id' => $role->id,
            'password' => 'password',
            'status' => $status,
        ]);
    }
}
