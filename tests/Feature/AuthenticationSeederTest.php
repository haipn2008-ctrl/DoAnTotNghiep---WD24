<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
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

    public function test_full_business_dataset_is_complete_correct_and_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('users', 30);
        $this->assertDatabaseCount('rooms', 18);
        $this->assertDatabaseCount('tenants', 32);
        $this->assertDatabaseCount('contracts', 16);
        $this->assertDatabaseCount('contract_tenants', 27);
        $this->assertDatabaseCount('contract_tenant_histories', 27);
        $this->assertDatabaseCount('utility_readings', 13);
        $this->assertDatabaseCount('invoices', 18);
        $this->assertDatabaseCount('invoice_details', 38);
        $this->assertDatabaseCount('payments', 16);
        $this->assertDatabaseCount('support_requests', 4);
        $this->assertDatabaseCount('vehicles', 3);
        $this->assertDatabaseCount('contract_extension_requests', 3);
        $this->assertDatabaseCount('contract_termination_requests', 3);
        $this->assertDatabaseCount('temporary_residences', 4);
        $this->assertDatabaseCount('settings', 1);

        $this->assertDatabaseHas('settings', [
            'bank_id' => 'MB',
            'bank_account_no' => '6666200066789',
            'bank_account_name' => 'NGUYEN XUAN NAM',
        ]);

        foreach ([
            Contract::STATUS_DRAFT,
            Contract::STATUS_PENDING_SIGNATURE,
            Contract::STATUS_PENDING_DEPOSIT,
            Contract::STATUS_AWAITING_MOVE_IN,
            Contract::STATUS_ACTIVE,
            Contract::STATUS_EXPIRED,
            Contract::STATUS_SETTLING,
            Contract::STATUS_COMPLETED,
            Contract::STATUS_CANCELLED,
        ] as $status) {
            $this->assertDatabaseHas('contracts', ['status' => $status]);
        }

        foreach ([
            Contract::DEPOSIT_PENDING,
            Contract::DEPOSIT_PAID,
            Contract::DEPOSIT_NEEDS_RESOLUTION,
            Contract::DEPOSIT_REFUND_REQUESTED,
            Contract::DEPOSIT_REFUND_APPROVED,
            Contract::DEPOSIT_REFUND_PROCESSING,
            Contract::DEPOSIT_REFUNDED,
            Contract::DEPOSIT_DEDUCTED,
            Contract::DEPOSIT_RETAINED,
        ] as $status) {
            $this->assertDatabaseHas('contracts', ['deposit_status' => $status]);
        }

        foreach ([Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_PAID, Invoice::STATUS_WRITTEN_OFF] as $status) {
            $this->assertDatabaseHas('invoices', ['invoice_type' => Invoice::TYPE_RENTAL, 'status' => $status]);
        }
        foreach (['pending', 'success', 'failed'] as $status) {
            $this->assertDatabaseHas('payments', ['status' => $status]);
        }
        foreach (['new', 'in_progress', 'resolved', 'rejected'] as $status) {
            $this->assertDatabaseHas('support_requests', ['status' => $status]);
        }
        foreach (['pending', 'approved', 'rejected'] as $status) {
            $this->assertDatabaseHas('vehicles', ['status' => $status]);
        }

        $this->assertSame(0, Contract::query()->whereColumn('deposit_amount', '!=', 'monthly_rent')->count());
        $this->assertSame(0, Invoice::query()->where('invoice_type', Invoice::TYPE_FIRST_MONTH_RENT)->count());
        $this->assertSame(
            Invoice::query()->where('invoice_type', Invoice::TYPE_RENTAL)->count(),
            Invoice::query()->where('invoice_type', Invoice::TYPE_RENTAL)->where('internet_fee', 100000)->count(),
        );
        $this->assertSame(0, Invoice::query()->where('invoice_type', Invoice::TYPE_RENTAL)
            ->whereRaw('CAST(strftime("%d", invoice_date) AS INTEGER) != 5')->count());
    }

    public function test_authentication_accounts_are_seeded_idempotently(): void
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

    public function test_legacy_portal_scenario_seeder_remains_idempotent(): void
    {
        $this->seed([RoleSeeder::class, UserSeeder::class, AuthenticationScenarioSeeder::class]);
        $this->seed([RoleSeeder::class, UserSeeder::class, AuthenticationScenarioSeeder::class]);

        foreach ([
            'ducthanh.nguyen@example.test' => User::STATUS_ACTIVE,
            'quynhanh.vu@example.test' => User::STATUS_SETTLING,
        ] as $email => $status) {
            $user = User::with('tenant.contracts.room', 'tenant.contracts.invoices.details')->where('email', $email)->sole();
            $this->assertSame($status, $user->status);
            $this->assertNotNull($user->tenant);
            $this->assertCount(1, $user->tenant->contracts);
            $this->assertNotNull($user->tenant->contracts->first()->room);
            $this->assertCount(1, $user->tenant->contracts->first()->invoices);
            $this->assertCount(5, $user->tenant->contracts->first()->invoices->first()->details);
        }
    }
}
