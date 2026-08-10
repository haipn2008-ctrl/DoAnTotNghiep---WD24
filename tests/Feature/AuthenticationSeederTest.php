<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AuthenticationScenarioSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_demo_dataset_is_complete_and_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('users', 17);
        $this->assertDatabaseCount('rooms', 15);
        $this->assertDatabaseCount('tenants', 11);
        $this->assertDatabaseCount('contracts', 11);
        $this->assertDatabaseCount('utility_readings', 33);
        $this->assertDatabaseCount('invoices', 33);
        $this->assertDatabaseCount('invoice_details', 165);
        $this->assertDatabaseCount('payments', 28);
        $this->assertDatabaseCount('support_requests', 10);
        $this->assertDatabaseCount('settings', 1);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@nhatroanphuc.test',
            'name' => 'Nguyễn Minh Hoàng',
        ]);
        $this->assertDatabaseHas('rooms', [
            'room_code' => 'B201',
            'description' => 'Phòng rộng có ban công hướng đông, đón nắng sáng và nhiều ánh sáng tự nhiên.',
        ]);
        $this->assertDatabaseHas('tenants', [
            'full_name' => 'Đặng Khánh Linh',
            'address' => 'Nam Định, Nam Định',
        ]);
        $this->assertDatabaseHas('support_requests', [
            'subject' => 'Vòi nước bồn rửa bị rò nhẹ',
            'status' => 'new',
        ]);

        foreach (['pending', 'success', 'failed'] as $status) {
            $this->assertDatabaseHas('payments', ['status' => $status]);
        }

        foreach (['new', 'in_progress', 'resolved', 'rejected'] as $status) {
            $this->assertDatabaseHas('support_requests', ['status' => $status]);
        }
    }

    public function test_authentication_scenario_accounts_are_seeded_idempotently(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class]);
        $this->seed([RoleSeeder::class, UserSeeder::class]);

        $expectedAccounts = [
            'auth.admin@example.test' => [User::STATUS_ACTIVE, 'Admin'],
            'auth.client@example.test' => [User::STATUS_ACTIVE, 'User'],
            'auth.pending@example.test' => [User::STATUS_PENDING, 'User'],
            'auth.settling@example.test' => [User::STATUS_SETTLING, 'User'],
            'auth.locked@example.test' => [User::STATUS_LOCKED, 'User'],
            'auth.inactive@example.test' => [User::STATUS_INACTIVE, 'User'],
            'auth.unsupported-role@example.test' => [User::STATUS_ACTIVE, 'Auditor'],
        ];

        foreach ($expectedAccounts as $email => [$status, $role]) {
            $user = User::with('role')->where('email', $email)->sole();

            $this->assertSame($status, $user->status);
            $this->assertSame($role, $user->role->role_name);
            $this->assertTrue(Hash::check('Auth@123456', $user->password));
        }

        $this->assertSame(count($expectedAccounts), User::whereIn('email', array_keys($expectedAccounts))->count());
        $this->assertTrue(User::where('email', 'auth.pending@example.test')->sole()->must_change_password);
        $this->assertFalse(User::where('email', 'auth.client@example.test')->sole()->must_change_password);
    }

    public function test_portal_scenario_data_is_seeded_idempotently_for_client_lifecycle_accounts(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class, AuthenticationScenarioSeeder::class]);
        $this->seed([RoleSeeder::class, UserSeeder::class, AuthenticationScenarioSeeder::class]);

        foreach ([
            'auth.client@example.test' => User::STATUS_ACTIVE,
            'auth.pending@example.test' => User::STATUS_PENDING,
            'auth.settling@example.test' => User::STATUS_SETTLING,
        ] as $email => $status) {
            $user = User::with('tenant.contracts.room', 'tenant.contracts.invoices.details')
                ->where('email', $email)
                ->sole();

            $this->assertSame($status, $user->status);
            $this->assertNotNull($user->tenant);
            $this->assertCount(1, $user->tenant->contracts);
            $this->assertNotNull($user->tenant->contracts->first()->room);
            $this->assertCount(1, $user->tenant->contracts->first()->invoices);
            $this->assertCount(5, $user->tenant->contracts->first()->invoices->first()->details);
        }

        $active = User::where('email', 'auth.client@example.test')->sole();
        $this->actingAs($active)->get('/client')->assertOk()->assertSee('AUTH-ACTIVE');
        $this->get('/client/contracts')->assertOk()->assertSee('HD-AUTH-ACTIVE');
        $this->get('/client/invoices')->assertOk()->assertSee('INV-AUTH-ACTIVE');
        $this->get('/client/utilities')->assertOk()->assertSee('AUTH-ACTIVE');

        $settling = User::where('email', 'auth.settling@example.test')->sole();
        $this->actingAs($settling)->get('/client')->assertOk()->assertSee('AUTH-SETTLING');
        $this->get('/client/contracts')->assertOk()->assertSee('HD-AUTH-SETTLING');
        $this->get('/client/invoices')->assertOk()->assertSee('INV-AUTH-SETTLING');
    }
}
