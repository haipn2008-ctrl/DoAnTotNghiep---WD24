<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ContractLifecycleService;
use App\Services\TenantAccountLifecycle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndOfContractReminderAndFormerTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_job_notifies_both_sides_at_30_days_without_duplicates_and_extension_resolves_alert(): void
    {
        Carbon::setTestNow('2026-08-25 08:00:00');

        try {
            [$admin, $client, $tenant, $room, $contract] = $this->fixture(Contract::STATUS_ACTIVE);
            $contract->forceFill(['end_date' => today()->addDays(30)])->save();

            $first = app(ContractLifecycleService::class)->processDailyAlerts();
            $second = app(ContractLifecycleService::class)->processDailyAlerts();

            $this->assertSame(1, $first['alerts_created']);
            $this->assertSame(0, $second['alerts_created']);
            $this->assertDatabaseCount('contract_lifecycle_alerts', 1);
            $this->assertDatabaseCount('notifications', 1);
            $this->assertDatabaseHas('contract_lifecycle_alerts', [
                'contract_id' => $contract->id,
                'type' => 'contract_expiring',
                'resolved_at' => null,
            ]);

            app(ContractLifecycleService::class)->extendContract(
                $contract,
                $admin,
                today()->addYear(),
                'Khách tiếp tục thuê.'
            );

            $this->assertNotNull(ContractLifecycleAlert::query()->sole()->resolved_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_completed_tenant_keeps_read_only_portal_access(): void
    {
        [, $client, $tenant, , $contract] = $this->fixture(Contract::STATUS_COMPLETED);

        $status = app(TenantAccountLifecycle::class)->sync($tenant);

        $this->assertSame(User::STATUS_FORMER, $status);
        $this->assertTrue($client->fresh()->canAccessPortal());

        $this->actingAs($client->fresh())
            ->get(route('client.contracts.index'))
            ->assertSuccessful();
        $this->get(route('client.contracts.show', $contract))->assertSuccessful();
        $this->get(route('client.invoices.index'))->assertSuccessful();

        $this->post(route('client.extension-requests.store'), [
            'contract_id' => $contract->id,
            'requested_end_date' => today()->addYear()->toDateString(),
            'reason' => 'Thử thay đổi hợp đồng đã hoàn tất.',
        ])->assertRedirect(route('client.invoices.index'));
    }

    private function fixture(string $contractStatus): array
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'end-cycle-admin@example.test',
            'role_id' => $adminRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $client = User::create([
            'name' => 'Client', 'email' => 'end-cycle-client@example.test',
            'role_id' => $clientRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $client->id, 'full_name' => 'Khách cuối hợp đồng',
            'cccd' => '079000008888', 'phone' => '0900008888',
        ]);
        $room = Room::create([
            'room_code' => 'END-01', 'floor' => 1, 'price' => 3000000,
            'area' => 24, 'max_people' => 2, 'current_people' => $contractStatus === Contract::STATUS_ACTIVE ? 1 : 0,
            'status' => $contractStatus === Contract::STATUS_ACTIVE ? Room::STATUS_OCCUPIED : Room::STATUS_AVAILABLE,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-END-01', 'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000, 'deposit_amount' => 0,
            'deposit_status' => Contract::DEPOSIT_NOT_REQUIRED, 'deposit_resolution' => Contract::DEPOSIT_NOT_REQUIRED,
            'start_date' => today()->subYear(), 'end_date' => today()->addMonth(),
            'actual_move_in_at' => now()->subYear(),
            'actual_move_out_at' => $contractStatus === Contract::STATUS_COMPLETED ? now()->subDay() : null,
            'completed_at' => $contractStatus === Contract::STATUS_COMPLETED ? now()->subDay() : null,
            'status' => $contractStatus,
        ]);

        return [$admin, $client, $tenant, $room, $contract];
    }
}
