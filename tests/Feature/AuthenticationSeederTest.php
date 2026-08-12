<?php

namespace Tests\Feature;

use App\Models\Contract;
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
        $this->assertDatabaseCount('users', 30);
        $this->assertDatabaseCount('rooms', 15);
        $this->assertDatabaseCount('tenants', 33);
        $this->assertDatabaseCount('contracts', 11);
        $this->assertDatabaseCount('contract_occupants', 23);
        $this->assertDatabaseCount('contract_occupant_histories', 23);
        $this->assertDatabaseCount('utility_readings', 40);
        $this->assertDatabaseCount('invoices', 33);
        $this->assertDatabaseCount('invoice_details', 165);
        $this->assertDatabaseCount('payments', 28);
        $this->assertDatabaseCount('support_requests', 10);
        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseHas('settings', [
            'property_name' => 'Nhà trọ StayMaster',
            'property_address' => 'Trịnh Văn Bô, Nam Từ Liêm, Hà Nội',
            'landlord_name' => 'Nguyễn Xuân Nam',
            'landlord_identity_number' => '001206006081',
            'landlord_phone' => '0961152763',
            'parking_fee' => 75000,
            'bank_id' => 'MB',
            'bank_account_no' => '6666200066789',
            'bank_account_name' => 'NGUYEN XUAN NAM',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@nhatroanphuc.test',
            'name' => 'Nguyễn Minh Hoàng',
        ]);
        $this->assertDatabaseHas('rooms', [
            'room_code' => 'B201',
            'description' => 'Phòng rộng hướng đông, đón nắng sáng và nhiều ánh sáng tự nhiên.',
        ]);
        $this->assertDatabaseHas('tenants', [
            'full_name' => 'Đặng Khánh Linh',
            'address' => 'Nam Định, Nam Định',
        ]);
        $this->assertDatabaseHas('tenants', [
            'full_name' => 'Phạm Thảo Vy',
            'user_id' => null,
        ]);
        $availableTenants = User::query()
            ->whereBetween('phone', ['0936102001', '0936102010'])
            ->with(['tenant.contracts', 'tenant.contractOccupancies'])
            ->get();
        $this->assertCount(10, $availableTenants);
        foreach ($availableTenants as $availableTenant) {
            $this->assertNotNull($availableTenant->tenant);
            $this->assertNotEmpty($availableTenant->name);
            $this->assertNotEmpty($availableTenant->tenant->date_of_birth);
            $this->assertNotEmpty($availableTenant->tenant->gender);
            $this->assertMatchesRegularExpression('/^\\d{12}$/', $availableTenant->tenant->cccd);
            $this->assertNotEmpty($availableTenant->tenant->phone);
            $this->assertNotEmpty($availableTenant->tenant->email);
            $this->assertNotEmpty($availableTenant->tenant->address);
            $this->assertCount(0, $availableTenant->tenant->contracts);
            $this->assertCount(0, $availableTenant->tenant->contractOccupancies);
        }
        $b201 = Contract::with('occupants')->where('contract_code', 'HD-AP-2026-004')->sole();
        $this->assertSame(3, $b201->number_of_people);
        $this->assertCount(3, $b201->occupants);
        $this->assertCount(1, $b201->occupants->where('role', 'representative'));
        $this->assertCount(2, $b201->occupants->where('role', 'occupant'));
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
            'ducthanh.nguyen@example.test' => [User::STATUS_ACTIVE, 'User'],
            'minhkhang.le@example.test' => [User::STATUS_PENDING, 'User'],
            'quynhanh.vu@example.test' => [User::STATUS_SETTLING, 'User'],
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
        $this->assertTrue(User::where('email', 'minhkhang.le@example.test')->sole()->must_change_password);
        $this->assertFalse(User::where('email', 'ducthanh.nguyen@example.test')->sole()->must_change_password);

        foreach (['qa.client.a@example.test', 'qa.client.b@example.test', 'qa.client.c@example.test'] as $email) {
            $qaUser = User::with('role')->where('email', $email)->sole();
            $this->assertSame(User::STATUS_ACTIVE, $qaUser->status);
            $this->assertSame('User', $qaUser->role->role_name);
            $this->assertTrue(Hash::check('Test@123456', $qaUser->password));
            $this->assertNull($qaUser->tenant);
        }
    }

    public function test_portal_scenario_data_is_seeded_idempotently_for_client_lifecycle_accounts(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class, AuthenticationScenarioSeeder::class]);
        $this->seed([RoleSeeder::class, UserSeeder::class, AuthenticationScenarioSeeder::class]);

        foreach ([
            'ducthanh.nguyen@example.test' => User::STATUS_ACTIVE,
            'minhkhang.le@example.test' => User::STATUS_PENDING,
            'quynhanh.vu@example.test' => User::STATUS_SETTLING,
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

        $active = User::where('email', 'ducthanh.nguyen@example.test')->sole();
        $this->actingAs($active)->get('/client')->assertOk()->assertSee('D401');
        $this->get('/client/contracts')->assertOk()->assertSee('HD20260009');
        $this->get('/client/invoices')->assertOk()->assertSee('INV-D401');
        $this->get('/client/utilities')->assertOk()->assertSee('D401');

        $settling = User::where('email', 'quynhanh.vu@example.test')->sole();
        $this->actingAs($settling)->get('/client')->assertOk()->assertSee('D403');
        $this->get('/client/contracts')->assertOk()->assertSee('HD20250011');
        $this->get('/client/invoices')->assertOk()->assertSee('INV-D403');
    }
}
