<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\SettlementStatement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepositRefundLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlement_portal_lists_each_owned_settlement_contract(): void
    {
        [, $client, $contract] = $this->fixture();
        $secondRoom = Room::create([
            'room_code' => 'REFUND-02', 'floor' => 2, 'price' => 2500000,
            'area' => 22, 'max_people' => 2, 'status' => Room::STATUS_AVAILABLE,
        ]);
        $completedContract = Contract::query()->forceCreate([
            'contract_code' => 'HD-REFUND-02',
            'room_id' => $secondRoom->id,
            'tenant_id' => $contract->tenant_id,
            'monthly_rent' => 2500000,
            'deposit_amount' => 0,
            'start_date' => today()->subYears(2),
            'end_date' => today()->subYear(),
            'actual_move_in_at' => now()->subYears(2),
            'actual_move_out_at' => now()->subYear(),
            'status' => Contract::STATUS_COMPLETED,
        ]);
        SettlementStatement::query()->create([
            'contract_id' => $completedContract->id,
            'status' => SettlementStatement::STATUS_SETTLED,
            'final_charge_amount' => 0,
            'previous_outstanding_amount' => 0,
            'deposit_credit' => 0,
            'net_amount' => 0,
            'calculated_at' => now(),
        ]);

        $this->actingAs($client)->get(route('client.settlement.index'))
            ->assertSuccessful()
            ->assertSee('HD-REFUND-01')
            ->assertSee('HD-REFUND-02')
            ->assertSee('Tài khoản đang được duy trì để hoàn tất quyết toán.');
    }

    public function test_client_can_request_refund_only_while_contract_is_settling(): void
    {
        [, $client, $contract] = $this->fixture();

        $this->actingAs($client)
            ->get(route('client.deposit-refunds.index', $contract))
            ->assertSuccessful()
            ->assertSee('Thông tin nhận tiền');

        $this->actingAs($client)->post(route('client.deposit-refunds.store', $contract), [
            'bank_name' => 'VCB',
            'bank_account_number' => '0123456789',
            'bank_account_name' => 'NGUYEN VAN A',
        ])->assertRedirect(route('client.deposit-refunds.index', $contract));

        $this->assertTrue($contract->fresh()->isRefundRequested());
        $this->get(route('client.deposit-refunds.index', $contract))
            ->assertSuccessful()
            ->assertSee('Thông tin nhận tiền đã gửi');
    }

    public function test_admin_can_transfer_the_exact_refund_amount_without_rounding_to_thousands(): void
    {
        Storage::fake('public');
        [$admin, , $contract] = $this->fixture([
            'deposit_status' => Contract::DEPOSIT_REFUND_APPROVED,
            'deposit_refund_amount' => 238710,
            'deposit_bank_name' => 'MB',
            'deposit_bank_account_number' => '0123456789',
            'deposit_bank_account_name' => 'NGUYEN VAN A',
        ]);

        $this->actingAs($admin)->get(route('admin.deposit-refunds.index'))
            ->assertSuccessful()
            ->assertSee('value="238710"', false)
            ->assertSee('step="1"', false);

        $this->post(route('admin.deposit-refunds.complete', $contract), [
            'transfer_amount' => 238710,
            'transfer_proof' => UploadedFile::fake()->image('refund-238710.jpg'),
        ])->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame(238710.0, (float) $contract->deposit_transfer_amount);
        $this->assertNotNull($contract->deposit_transfer_proof);
    }

    public function test_refund_transfer_does_not_bypass_the_final_settlement_gate(): void
    {
        Storage::fake('public');
        [$admin, $client, $contract] = $this->fixture([
            'deposit_status' => Contract::DEPOSIT_REFUND_REQUESTED,
        ]);

        $this->actingAs($admin)->post(route('admin.deposit-refunds.approve', $contract), [
            'deposit_process_type' => 'full_refund',
            'return_reason' => 'Phòng và tài sản được bàn giao đầy đủ.',
        ])->assertSessionHas('success');

        $this->post(route('admin.deposit-refunds.complete', $contract), [
            'transfer_amount' => 2000000,
            'transfer_proof' => UploadedFile::fake()->image('refund.jpg'),
        ])->assertSessionHas('success');

        $contract->refresh();
        $this->assertSame(Contract::STATUS_SETTLING, $contract->status);
        $this->assertSame(Contract::DEPOSIT_REFUNDED, $contract->deposit_resolution);
        $this->assertTrue($contract->isRefundCompleted());
        $this->assertSame(User::STATUS_SETTLING, $client->fresh()->status);

        $this->actingAs($client)
            ->get(route('client.deposit-refunds.index', $contract))
            ->assertSuccessful()
            ->assertSee('Tiền hoàn đã được chuyển')
            ->assertSee(route('client.deposit-refunds.proof', $contract), false);
        $this->get(route('client.deposit-refunds.proof', $contract))->assertSuccessful();

        $this->actingAs($admin)->post(route('admin.contracts.complete-settlement', $contract), [
            'confirm_complete' => '1',
        ])->assertSessionHas('success');

        $this->assertSame(Contract::STATUS_COMPLETED, $contract->fresh()->status);
        $this->assertSame(User::STATUS_FORMER, $client->fresh()->status);
    }

    public function test_forfeiting_deposit_records_resolution_without_completing_contract(): void
    {
        Storage::fake('public');
        [$admin, , $contract] = $this->fixture([
            'deposit_status' => Contract::DEPOSIT_REFUND_REQUESTED,
        ]);

        $this->actingAs($admin)->post(route('admin.deposit-refunds.approve', $contract), [
            'deposit_process_type' => 'no_refund',
            'return_reason' => 'Khấu trừ theo biên bản hư hỏng.',
            'damage_proof' => UploadedFile::fake()->image('damage.jpg'),
        ])->assertSessionHas('success');

        $contract->refresh();
        $this->assertSame(Contract::STATUS_SETTLING, $contract->status);
        $this->assertSame(Contract::DEPOSIT_RETAINED, $contract->deposit_resolution);
        $this->assertSame(Contract::DEPOSIT_FORFEITED, $contract->deposit_status);
        $this->assertSame(0.0, (float) $contract->settlementStatement->fresh()->net_amount);
    }

    public function test_full_refund_is_capped_by_the_balance_after_all_debts_are_offset(): void
    {
        [$admin, , $contract] = $this->fixture([
            'deposit_status' => Contract::DEPOSIT_REFUND_REQUESTED,
        ]);
        Invoice::query()->forceCreate([
            'invoice_code' => 'INV-REFUND-DEBT',
            'contract_id' => $contract->id,
            'room_id' => $contract->room_id,
            'invoice_type' => Invoice::TYPE_RENTAL,
            'month' => today()->subMonth()->month,
            'year' => today()->subMonth()->year,
            'invoice_date' => today()->subMonth(),
            'due_date' => today()->subMonth()->addDays(10),
            'room_fee' => 500000,
            'total_amount' => 500000,
            'status' => Invoice::STATUS_UNPAID,
        ]);

        $this->actingAs($admin)->post(route('admin.deposit-refunds.approve', $contract), [
            'deposit_process_type' => 'full_refund',
            'return_reason' => 'Hoàn phần cọc còn lại sau khi bù trừ công nợ.',
        ])->assertSessionHas('success');

        $contract->refresh();
        $this->assertSame(1500000.0, (float) $contract->deposit_refund_amount);
        $this->assertSame(500000.0, (float) $contract->deposit_deduction_amount);
        $this->assertSame(-1500000.0, (float) $contract->settlementStatement->fresh()->net_amount);
        $debt = Invoice::query()->where('invoice_code', 'INV-REFUND-DEBT')->firstOrFail();
        $this->assertSame(Invoice::STATUS_PAID, $debt->status);
        $this->assertSame(0.0, (float) $debt->remaining_amount);
    }

    private function fixture(array $contractOverrides = []): array
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'refund-admin@example.test', 'role_id' => $adminRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $client = User::create([
            'name' => 'Client', 'email' => 'refund-client@example.test', 'role_id' => $clientRole->id,
            'password' => 'password', 'status' => User::STATUS_SETTLING,
        ]);
        $tenant = Tenant::create([
            'user_id' => $client->id, 'full_name' => 'Khách hoàn cọc',
            'cccd' => '079000008888', 'phone' => '0900008888',
        ]);
        $room = Room::create([
            'room_code' => 'REFUND-01', 'floor' => 1, 'price' => 2000000,
            'area' => 20, 'max_people' => 2, 'status' => Room::STATUS_AVAILABLE,
        ]);
        $contract = Contract::query()->forceCreate(array_merge([
            'contract_code' => 'HD-REFUND-01', 'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'monthly_rent' => 2000000, 'deposit_amount' => 2000000, 'number_of_people' => 1,
            'start_date' => today()->subYear(), 'end_date' => today()->subDay(),
            'actual_move_in_at' => now()->subYear(), 'actual_move_out_at' => now()->subDay(),
            'deposit_status' => Contract::DEPOSIT_PAID,
            'status' => Contract::STATUS_SETTLING,
        ], $contractOverrides));
        SettlementStatement::query()->create([
            'contract_id' => $contract->id,
            'status' => SettlementStatement::STATUS_AWAITING_REFUND,
            'final_charge_amount' => 0,
            'previous_outstanding_amount' => 0,
            'deposit_credit' => 2000000,
            'net_amount' => -2000000,
            'calculated_at' => now(),
        ]);

        return [$admin, $client, $contract];
    }
}
