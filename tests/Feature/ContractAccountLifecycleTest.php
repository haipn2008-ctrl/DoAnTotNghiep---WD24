<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Services\TenantAccountLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ContractAccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_ending_contract_moves_debt_account_to_settling_then_inactive_after_payment(): void
    {
        [$admin, $client, $tenant, $room, $contract] = $this->rentalFixture();

        $invoice = Invoice::create([
            'invoice_code' => 'INV-LIFECYCLE-1',
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'month' => 8,
            'year' => 2026,
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'room_fee' => 2000000,
            'total_amount' => 2000000,
            'status' => Invoice::STATUS_UNPAID,
        ]);

        $this->actingAs($admin)->post(route('admin.contracts.end', $contract->id), [
            'actual_end_date' => '2026-08-10',
            'termination_reason' => 'expired',
            'confirm_end' => '1',
            'checkout_electricity' => 120,
            'checkout_water' => 55,
        ])->assertRedirect(route('admin.contracts.show', $contract));

        $this->assertSame(User::STATUS_SETTLING, $client->fresh()->status);
        $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
        $this->assertSame(Contract::STATUS_SETTLING, $contract->fresh()->status);

        $this->actingAs($client->fresh())
            ->get(route('client.room.show'))
            ->assertRedirect(route('client.invoices.index'));
        $this->get(route('client.invoices.index'))->assertSuccessful();

        $this->actingAs($admin)->post(route('admin.invoices.payments.store', $invoice), [
            'amount_paid' => 2000000,
            'payment_date' => '2026-08-10',
            'payment_method' => 'cash',
        ])->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertSame(User::STATUS_SETTLING, $client->fresh()->status);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount_paid' => 2000000,
            'status' => 'success',
        ]);
    }

    public function test_inactive_former_tenant_cannot_be_assigned_to_a_new_contract(): void
    {
        [$admin, $client, $tenant, $room] = $this->rentalFixture();
        $client->update(['status' => User::STATUS_INACTIVE]);
        $room->update(['status' => Room::STATUS_AVAILABLE]);

        $newRoom = Room::create([
            'room_code' => 'P-NEW',
            'floor' => 1,
            'price' => 2500000,
            'area' => 25,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)->post(route('admin.contracts.store'), [
            'room_id' => $newRoom->id,
            'tenant_id' => $tenant->id,
            'start_date' => '2026-09-01',
            'contract_duration' => '12',
            'end_date' => '2027-09-01',
            'scheduled_move_in_date' => '2026-09-01',
            'reservation_expires_at' => '2026-09-02 18:00:00',
            'move_in_terms_confirmed' => 1,
            'representative_is_occupant' => 1,
            'representative' => [
                'identity_front' => UploadedFile::fake()->image('cccd-front.jpg'),
                'identity_back' => UploadedFile::fake()->image('cccd-back.jpg'),
            ],
            'number_of_people' => 1,
        ])->assertSessionHasErrors('tenant_id');

        $this->assertDatabaseMissing('contracts', ['room_id' => $newRoom->id]);
    }

    public function test_ending_last_contract_without_debt_deactivates_account(): void
    {
        [$admin, $client, $tenant, $room, $contract] = $this->rentalFixture();

        $this->actingAs($admin)->post(route('admin.contracts.end', $contract), [
            'actual_end_date' => '2026-08-10',
            'termination_reason' => 'expired',
            'confirm_end' => '1',
            'checkout_electricity' => 120,
            'checkout_water' => 55,
        ])->assertRedirect(route('admin.contracts.show', $contract));

        $this->assertSame(User::STATUS_SETTLING, $client->fresh()->status);
        $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
        $this->assertSame(Contract::STATUS_TERMINATED, $contract->fresh()->status);
    }

    public function test_ending_one_contract_keeps_account_active_when_another_contract_is_open(): void
    {
        [$admin, $client, $tenant, $room, $contract] = $this->rentalFixture();
        $otherRoom = Room::create([
            'room_code' => 'P-OTHER',
            'floor' => 2,
            'price' => 2500000,
            'area' => 25,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        Contract::query()->forceCreate([
            'contract_code' => 'HD-LIFECYCLE-2',
            'room_id' => $otherRoom->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 2500000,
            'start_date' => '2026-08-01',
            'end_date' => '2027-08-01',
            'status' => Contract::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)->post(route('admin.contracts.end', $contract), [
            'actual_end_date' => '2026-08-10',
            'termination_reason' => 'other',
            'confirm_end' => '1',
            'checkout_electricity' => 120,
            'checkout_water' => 55,
        ])->assertRedirect(route('admin.contracts.show', $contract));

        $this->assertSame(User::STATUS_ACTIVE, $client->fresh()->status);
        $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $otherRoom->fresh()->status);
    }

    public function test_partial_or_pending_payment_does_not_release_settling_account(): void
    {
        [, $client, $tenant, $room, $contract] = $this->rentalFixture();
        $contract->forceFill(['status' => Contract::STATUS_SETTLING, 'actual_move_out_at' => now()])->save();
        $invoice = Invoice::create([
            'invoice_code' => 'INV-LIFECYCLE-PARTIAL',
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'month' => 8,
            'year' => 2026,
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'room_fee' => 2000000,
            'total_amount' => 2000000,
            'status' => Invoice::STATUS_PARTIAL,
        ]);

        app(TenantAccountLifecycle::class)->sync($tenant);
        $this->assertSame(User::STATUS_SETTLING, $client->fresh()->status);

        $invoice->update(['status' => Invoice::STATUS_UNPAID]);
        app(TenantAccountLifecycle::class)->sync($tenant);
        $this->assertSame(User::STATUS_SETTLING, $client->fresh()->status);
    }

    public function test_sync_preserves_manual_lock_and_pending_activation(): void
    {
        [, $client, $tenant] = $this->rentalFixture();

        foreach ([User::STATUS_LOCKED, User::STATUS_PENDING] as $status) {
            $client->update(['status' => $status]);

            $result = app(TenantAccountLifecycle::class)->sync($tenant->fresh());

            $this->assertSame($status, $result);
            $this->assertSame($status, $client->fresh()->status);
        }
    }

    public function test_sync_without_linked_user_is_safe_and_changes_nothing(): void
    {
        $tenant = Tenant::create([
            'full_name' => 'Khách chưa có tài khoản',
            'cccd' => '079000000099',
            'phone' => '0900000099',
        ]);

        $this->assertNull(app(TenantAccountLifecycle::class)->sync($tenant));
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'user_id' => null]);
    }

    public function test_invalid_actual_end_date_does_not_change_contract_room_or_account(): void
    {
        [$admin, $client, , $room, $contract] = $this->rentalFixture();

        foreach (['2025-08-09', now()->addDay()->toDateString()] as $actualEndDate) {
            $this->actingAs($admin)->from(route('admin.contracts.end.form', $contract))->post(
                route('admin.contracts.end', $contract),
                [
                    'actual_end_date' => $actualEndDate,
                    'termination_reason' => 'expired',
                    'confirm_end' => '1',
                    'checkout_electricity' => 120,
                    'checkout_water' => 55,
                ]
            )->assertRedirect(route('admin.contracts.end.form', $contract))
                ->assertSessionHasErrors('actual_move_out_at');

            $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
            $this->assertSame(Room::STATUS_OCCUPIED, $room->fresh()->status);
            $this->assertSame(User::STATUS_ACTIVE, $client->fresh()->status);
        }
    }

    private function rentalFixture(): array
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-lifecycle@example.com',
            'role_id' => $adminRole->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
        $client = User::create([
            'name' => 'Khách cũ',
            'email' => 'client-lifecycle@example.com',
            'role_id' => $clientRole->id,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $client->id,
            'full_name' => 'Khách cũ',
            'cccd' => '079000000001',
            'phone' => '0900000001',
        ]);
        $room = Room::create([
            'room_code' => 'P-OLD',
            'floor' => 1,
            'price' => 2000000,
            'area' => 20,
            'current_people' => 1,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-LIFECYCLE-1',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 2000000,
            'deposit_amount' => 2000000,
            'number_of_people' => 1,
            'start_date' => '2025-08-10',
            'end_date' => '2026-08-10',
            'signed_at' => '2025-08-01 10:00:00',
            'actual_move_in_at' => '2025-08-10 10:00:00',
            'status' => 'active',
        ]);
        UtilityReading::create([
            'room_id' => $room->id, 'contract_id' => $contract->id,
            'month' => 8, 'year' => 2025, 'record_date' => '2025-08-10', 'reading_type' => 'handover',
            'electricity_old' => 100, 'electricity_new' => 100,
            'water_old' => 50, 'water_new' => 50, 'status' => 'confirmed',
        ]);

        return [$admin, $client, $tenant, $room, $contract];
    }
}
