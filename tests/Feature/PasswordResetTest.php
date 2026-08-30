<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_can_open_forgot_password_form_from_login(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('password.request'), false);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertViewIs('auth.forgot-password')
            ->assertSee('name="email"', false);
    }

    public function test_reset_link_is_sent_only_to_a_registered_email(): void
    {
        Notification::fake();
        $user = $this->user('registered@example.com');

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        $this->assertDatabaseHas('password_reset_otps', ['email' => $user->email]);

        $this->post('/forgot-password', ['email' => 'unknown@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNotSentTo(
            new User(['email' => 'unknown@example.com']),
            ResetPasswordNotification::class
        );
        $this->assertDatabaseMissing('password_reset_otps', ['email' => 'unknown@example.com']);
    }

    public function test_user_can_reset_password_with_the_emailed_token(): void
    {
        Notification::fake();
        $user = $this->user('client@example.com');
        $code = null;

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            }
        );

        $this->get(route('password.reset', ['email' => $user->email]))
            ->assertOk()
            ->assertViewIs('auth.reset-password');

        $this->post('/reset-password', [
            'code' => $code,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_otps', ['email' => $user->email]);
    }

    public function test_invalid_token_and_unconfirmed_password_are_rejected(): void
    {
        $user = $this->user('client@example.com');

        $this->from('/reset-password')->post('/reset-password', [
            'code' => '000000',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ])->assertRedirect('/reset-password')->assertSessionHasErrors('password');

        $this->from('/reset-password')->post('/reset-password', [
            'code' => '000000',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect('/reset-password')->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    private function user(string $email): User
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);

        return User::create([
            'name' => 'Người dùng kiểm thử',
            'email' => $email,
            'phone' => '0900000000',
            'role_id' => $role->id,
            'password' => 'old-password',
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
