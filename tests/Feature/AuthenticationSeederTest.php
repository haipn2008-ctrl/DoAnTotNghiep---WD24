<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_dataset_contains_only_base_management_data_and_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $trackedTables = [
            'roles', 'users', 'rooms', 'tenants', 'amenities', 'amenity_room',
            'settings', 'fee_schedules', 'contracts', 'invoices', 'payments',
            'support_requests', 'vehicles', 'contract_extension_requests',
            'contract_termination_requests', 'temporary_residences',
            'contract_appendices', 'contract_lifecycle_alerts', 'notifications',
        ];
        $countsAfterFirstRun = collect($trackedTables)
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()]);

        $this->seed(DatabaseSeeder::class);

        foreach ($countsAfterFirstRun as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }

        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('users', 12);
        $this->assertDatabaseCount('tenants', 10);
        $this->assertDatabaseCount('rooms', 12);
        $this->assertDatabaseCount('settings', 1);
        $this->assertSame(12, Room::query()->where('status', Room::STATUS_AVAILABLE)->count());

        foreach ([
            'contracts', 'invoices', 'payments', 'support_requests', 'vehicles',
            'contract_extension_requests', 'contract_termination_requests',
            'temporary_residences', 'contract_appendices', 'contract_lifecycle_alerts',
            'notifications',
        ] as $emptyTable) {
            $this->assertDatabaseCount($emptyTable, 0);
        }

        $this->assertDatabaseHas('settings', [
            'landlord_name' => 'Nguyễn Xuân Nam',
            'bank_id' => 'MB',
            'bank_account_no' => '6666200066789',
            'bank_account_name' => 'NGUYEN XUAN NAM',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@nhatroanphuc.test',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_optional_authentication_accounts_are_seeded_idempotently(): void
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
        ];

        foreach ($expectedAccounts as $email => [$status, $role]) {
            $user = User::with('role')->where('email', $email)->sole();
            $this->assertSame($status, $user->status);
            $this->assertSame($role, $user->role->role_name);
            $this->assertTrue(Hash::check('Auth@123456', $user->password));
        }
    }
}
