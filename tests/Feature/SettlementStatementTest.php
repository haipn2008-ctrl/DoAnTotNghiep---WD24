<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SettlementStatement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettlementStatementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_checkout_builds_itemized_final_statement_and_offsets_held_deposit(): void
    {
        Carbon::setTestNow('2026-08-10 10:00:00');
        try {
            [$admin, $contract] = $this->fixture();
            Invoice::query()->forceCreate([
                'invoice_code' => 'INV-OLD-DEBT', 'contract_id' => $contract->id,
                'room_id' => $contract->room_id, 'invoice_type' => Invoice::TYPE_RENTAL,
                'month' => 7, 'year' => 2026, 'invoice_date' => '2026-07-01', 'due_date' => '2026-07-05',
                'room_fee' => 300000, 'total_amount' => 300000, 'status' => Invoice::STATUS_UNPAID,
            ]);

            $this->actingAs($admin)->post(route('admin.contracts.check-out', $contract), [
                'actual_move_out_at' => '2026-08-10 10:00:00',
                'checkout_electricity' => 130,
                'checkout_water' => 17,
                'checkout_reason' => 'Bàn giao cuối hợp đồng.',
                'has_damage' => 1,
                'checkout_key_count' => 1,
                'handover_confirmed' => '1',
                'settlement_amount' => 200000,
                'settlement_description' => 'Bồi thường tài sản hư hỏng',
                'checkout_damage_note' => 'Tài sản bị hư hỏng khi bàn giao.',
                'checkout_photos' => [UploadedFile::fake()->image('damage.jpg')],
            ])->assertSessionHas('success');

            $statement = SettlementStatement::query()->with(['items', 'invoice.details'])->sole();
            $this->assertEqualsCanonicalizing(
                ['electricity', 'water', 'room', 'internet', 'service', 'adjustment'],
                $statement->items->pluck('type')->all()
            );
            $this->assertEqualsWithDelta(1308387, (float) $statement->final_charge_amount, 0.01);
            $this->assertEqualsWithDelta(300000, (float) $statement->previous_outstanding_amount, 0.01);
            $this->assertEqualsWithDelta(2000000, (float) $statement->deposit_credit, 0.01);
            $this->assertEqualsWithDelta(-391613, (float) $statement->net_amount, 0.01);
            $this->assertSame(SettlementStatement::STATUS_AWAITING_REFUND, $statement->status);
            $this->assertSame(Invoice::TYPE_SETTLEMENT, $statement->invoice->invoice_type);
            $this->assertEqualsWithDelta((float) $statement->final_charge_amount, (float) $statement->invoice->total_amount, 0.01);

            $oldInvoice = Invoice::query()->where('invoice_code', 'INV-OLD-DEBT')->firstOrFail();
            $this->assertSame(Invoice::STATUS_PAID, $oldInvoice->status);
            $this->assertSame(0.0, (float) $oldInvoice->remaining_amount);
            $this->assertSame(Invoice::STATUS_PAID, $statement->invoice->fresh()->status);
            $this->assertSame(0.0, (float) $statement->invoice->fresh()->remaining_amount);

            $contract->refresh();
            $this->assertSame(1608387.0, (float) $contract->deposit_deduction_amount);
            $this->assertSame(391613.0, (float) $contract->deposit_refund_amount);
            $this->assertSame(Contract::DEPOSIT_NEEDS_RESOLUTION, $contract->deposit_status);
            $this->assertTrue($contract->canRequestDepositRefund());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_client_only_pays_the_debt_remaining_after_automatic_deposit_offset(): void
    {
        Carbon::setTestNow('2026-08-10 10:00:00');
        try {
            [$admin, $contract] = $this->fixture();
            Invoice::query()->forceCreate([
                'invoice_code' => 'INV-DEBT-BEFORE-OFFSET', 'contract_id' => $contract->id,
                'room_id' => $contract->room_id, 'invoice_type' => Invoice::TYPE_RENTAL,
                'month' => 7, 'year' => 2026, 'invoice_date' => '2026-07-01', 'due_date' => '2026-07-05',
                'room_fee' => 1000000, 'total_amount' => 1000000, 'status' => Invoice::STATUS_UNPAID,
            ]);

            $this->actingAs($admin)->post(route('admin.contracts.check-out', $contract), [
                'actual_move_out_at' => '2026-08-10 10:00:00',
                'checkout_electricity' => 130,
                'checkout_water' => 17,
                'checkout_reason' => 'Bàn giao và tự động bù tiền cọc.',
                'has_damage' => 1,
                'checkout_key_count' => 1,
                'handover_confirmed' => '1',
                'settlement_amount' => 200000,
                'settlement_description' => 'Bồi thường tài sản hư hỏng',
                'checkout_damage_note' => 'Tài sản bị hư hỏng khi bàn giao.',
                'checkout_photos' => [UploadedFile::fake()->image('damage.jpg')],
            ])->assertSessionHas('success');

            $statement = SettlementStatement::query()->with('invoice')->sole();
            $this->assertSame(308387.0, (float) $statement->invoice->remaining_amount);
            $this->assertSame(308387.0, (float) $statement->net_amount);
            $this->assertSame(2000000.0, (float) $contract->fresh()->deposit_deduction_amount);
            $this->assertSame(0.0, (float) $contract->fresh()->deposit_refund_amount);
            $this->assertSame(Contract::DEPOSIT_DEDUCTED, $contract->fresh()->deposit_resolution);

            $this->post(route('admin.invoices.payments.store', $statement->invoice), [
                'amount_paid' => 308387,
                'payment_date' => '2026-08-10',
                'payment_method' => Payment::METHOD_CASH,
            ])->assertSessionHasNoErrors();

            $this->assertSame(Invoice::STATUS_PAID, $statement->invoice->fresh()->status);
            $this->assertSame(0.0, (float) $statement->fresh()->net_amount);

            $this->post(route('admin.contracts.complete-settlement', $contract), [
                'confirm_complete' => '1',
            ])->assertSessionHasNoErrors();

            $this->assertSame(Contract::STATUS_COMPLETED, $contract->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_checkout_does_not_charge_room_and_fixed_fees_twice_when_departure_month_was_billed(): void
    {
        Carbon::setTestNow('2026-08-10 10:00:00');
        try {
            [$admin, $contract] = $this->fixture();
            Invoice::query()->forceCreate([
                'invoice_code' => 'INV-CURRENT-MONTH', 'contract_id' => $contract->id,
                'room_id' => $contract->room_id, 'invoice_type' => Invoice::TYPE_RENTAL,
                'month' => 8, 'year' => 2026, 'invoice_date' => '2026-08-01', 'due_date' => '2026-08-15',
                'room_fee' => 3100000, 'internet_fee' => 100000, 'service_fee' => 50000,
                'total_amount' => 3250000, 'status' => Invoice::STATUS_PAID,
            ]);

            $this->actingAs($admin)->post(route('admin.contracts.check-out', $contract), [
                'actual_move_out_at' => '2026-08-10 10:00:00',
                'checkout_electricity' => 130,
                'checkout_water' => 17,
                'checkout_reason' => 'Bàn giao sau khi tháng hiện tại đã xuất hóa đơn.',
                'has_damage' => 0,
                'checkout_key_count' => 1,
                'handover_confirmed' => '1',
            ])->assertSessionHas('success');

            $statement = SettlementStatement::query()->with('items')->sole();
            $this->assertEqualsCanonicalizing(
                ['electricity', 'water'],
                $statement->items->pluck('type')->all()
            );
            $this->assertEqualsWithDelta(60000, (float) $statement->final_charge_amount, 0.01);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function fixture(): array
    {
        Setting::create([
            'electric_price' => 3000, 'water_price' => 15000,
            'internet_fee' => 100000, 'service_fee' => 50000,
            'parking_fee' => 0, 'invoice_day' => 5, 'payment_due_days' => 10,
        ]);
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'statement-admin@example.test', 'role_id' => $adminRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $client = User::create([
            'name' => 'Client', 'email' => 'statement-client@example.test', 'role_id' => $clientRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $client->id, 'full_name' => 'Khách quyết toán',
            'cccd' => '079000005555', 'phone' => '0900005555',
        ]);
        $room = Room::create([
            'room_code' => 'SETTLE-01', 'floor' => 1, 'price' => 3100000,
            'area' => 25, 'max_people' => 2, 'current_people' => 1,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-SETTLE-01', 'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'monthly_rent' => 3100000, 'deposit_amount' => 2000000,
            'deposit_status' => Contract::DEPOSIT_PAID, 'number_of_people' => 1,
            'start_date' => '2025-07-01', 'end_date' => '2026-08-31',
            'actual_move_in_at' => '2025-07-01 09:00:00', 'status' => Contract::STATUS_ACTIVE,
        ]);
        UtilityReading::query()->forceCreate([
            'room_id' => $room->id, 'contract_id' => $contract->id,
            'month' => 7, 'year' => 2026, 'record_date' => '2026-07-31',
            'reading_type' => 'periodic', 'electricity_old' => 100, 'electricity_new' => 120,
            'water_old' => 10, 'water_new' => 15, 'status' => UtilityReading::STATUS_CONFIRMED,
        ]);

        return [$admin, $contract];
    }
}
