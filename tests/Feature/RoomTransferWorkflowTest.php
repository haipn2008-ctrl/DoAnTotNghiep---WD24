<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Models\RoomTransferItem;
use App\Models\TemporaryResidence;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Services\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-08-15 09:00:00');
    }

    public function test_tenant_can_request_and_admin_can_complete_a_room_transfer_safely(): void
    {
        [$admin, $client, $contract, $oldRoom, $newRoom, $asset] = $this->fixture();
        $oldDebt = Invoice::query()->forceCreate([
            'invoice_code' => 'INV-OLD-DEBT', 'contract_id' => $contract->id, 'room_id' => $oldRoom->id,
            'invoice_type' => Invoice::TYPE_RENTAL, 'month' => 8, 'year' => 2026,
            'invoice_date' => '2026-08-05', 'due_date' => '2026-08-10',
            'room_fee' => 100000, 'total_amount' => 100000, 'status' => Invoice::STATUS_UNPAID,
        ]);
        Invoice::query()->forceCreate([
            'invoice_code' => 'INV-EXISTING-DEPOSIT', 'contract_id' => $contract->id, 'room_id' => $oldRoom->id,
            'invoice_type' => Invoice::TYPE_DEPOSIT, 'revision' => 1, 'month' => 8, 'year' => 2026,
            'invoice_date' => '2026-08-01', 'due_date' => '2026-08-01',
            'room_fee' => 0, 'total_amount' => 3000000, 'status' => Invoice::STATUS_PAID,
        ]);
        Invoice::query()->forceCreate([
            'invoice_code' => 'INV-EXISTING-SETTLEMENT', 'contract_id' => $contract->id, 'room_id' => $oldRoom->id,
            'invoice_type' => Invoice::TYPE_SETTLEMENT, 'revision' => 1, 'month' => 8, 'year' => 2026,
            'invoice_date' => '2026-08-01', 'due_date' => '2026-08-01',
            'room_fee' => 0, 'total_amount' => 0, 'status' => Invoice::STATUS_PAID,
        ]);
        $residence = TemporaryResidence::query()->create([
            'tenant_id' => $contract->tenant_id, 'contract_id' => $contract->id,
            'room_id' => $oldRoom->id, 'start_date' => '2026-07-01', 'status' => 'active',
        ]);

        $this->actingAs($client)->post(route('client.room-transfers.store'), [
            'contract_id' => $contract->id,
            'new_room_id' => $newRoom->id,
            'requested_transfer_date' => today()->toDateString(),
            'reason' => 'Cần phòng rộng hơn để ở cùng người thân.',
        ])->assertRedirect(route('client.room-transfers.index'))->assertSessionHasNoErrors();

        $transfer = RoomTransfer::query()->sole();
        $this->assertSame(RoomTransfer::STATUS_PENDING, $transfer->status);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'contract_id' => $contract->id, 'type' => 'room_transfer_request', 'resolved_at' => null,
        ]);

        $this->actingAs($admin)->post(route('admin.room-transfers.approve', $transfer), [
            'effective_date' => today()->toDateString(),
            'admin_reason' => 'Đã kiểm tra phòng trống và đồng ý chuyển.',
            'old_electricity' => 112,
            'old_water' => 24,
            'new_electricity' => 500,
            'new_water' => 60,
            'old_assets' => [$asset->id => ['quantity' => 1, 'condition' => 'damaged', 'note' => 'Trầy nhẹ']],
            'new_assets' => [$newRoom->id => [$asset->id => ['quantity' => 2, 'condition' => 'normal']]],
            'confirm_transfer' => '1',
        ])->assertRedirect(route('admin.room-transfers.index'))->assertSessionHasNoErrors();

        $transfer->refresh();
        $contract->refresh();
        $this->assertSame(RoomTransfer::STATUS_COMPLETED, $transfer->status);
        $this->assertSame($newRoom->id, $contract->room_id);
        $this->assertSame('3600000.00', $contract->monthly_rent);
        $this->assertSame('3600000.00', $contract->deposit_amount);
        $this->assertSame(Room::STATUS_AVAILABLE, $oldRoom->fresh()->status);
        $this->assertSame(0, $oldRoom->fresh()->current_people);
        $this->assertSame(Room::STATUS_OCCUPIED, $newRoom->fresh()->status);
        $this->assertSame(1, $newRoom->fresh()->current_people);
        $this->assertSame('cancelled', $residence->fresh()->status);
        $this->assertSame($oldRoom->id, $residence->fresh()->room_id, 'Hồ sơ tạm trú cũ phải giữ phòng lịch sử.');

        $this->assertDatabaseHas('utility_readings', [
            'contract_id' => $contract->id, 'room_id' => $oldRoom->id,
            'reading_type' => 'transfer_checkout', 'electricity_old' => 100, 'electricity_new' => 112,
        ]);
        $this->assertDatabaseHas('utility_readings', [
            'contract_id' => $contract->id, 'room_id' => $newRoom->id,
            'reading_type' => 'transfer_handover', 'electricity_old' => 500, 'electricity_new' => 500,
        ]);
        $this->assertDatabaseHas('room_transfer_items', [
            'room_transfer_id' => $transfer->id, 'room_id' => $oldRoom->id,
            'phase' => RoomTransferItem::PHASE_OLD_CHECKOUT, 'condition' => 'damaged',
        ]);
        $this->assertDatabaseHas('room_transfer_items', [
            'room_transfer_id' => $transfer->id, 'room_id' => $newRoom->id,
            'phase' => RoomTransferItem::PHASE_NEW_HANDOVER, 'quantity' => 2,
        ]);

        $this->assertSame($oldRoom->id, $oldDebt->fresh()->room_id, 'Công nợ cũ phải giữ nguyên phòng cũ.');
        $this->assertSame(100000.0, (float) $transfer->outstanding_amount);
        $this->assertSame($oldRoom->id, $transfer->transferInvoice->room_id);
        $this->assertSame(2, $transfer->transferInvoice->revision);
        $this->assertSame($newRoom->id, $transfer->depositInvoice->room_id);
        $this->assertSame(Invoice::TYPE_DEPOSIT, $transfer->depositInvoice->invoice_type);
        $this->assertSame(2, $transfer->depositInvoice->revision);
        $this->assertSame(600000.0, (float) $transfer->deposit_difference);
        $this->assertNotNull(ContractLifecycleAlert::query()->where('type', 'room_transfer_request')->sole()->resolved_at);
        $this->assertContains('room_transfer_completed', $client->notifications()->get()->pluck('data.type')->all());
    }

    public function test_future_invoice_is_prorated_to_the_new_room_after_transfer(): void
    {
        [$admin, , $contract, , $newRoom] = $this->fixture();
        $this->actingAs($admin)->post(route('admin.room-transfers.store', $contract), [
            'new_room_id' => $newRoom->id,
            'effective_date' => today()->toDateString(),
            'admin_reason' => 'Chủ động bố trí phòng phù hợp hơn.',
            'old_electricity' => 100,
            'old_water' => 20,
            'new_electricity' => 500,
            'new_water' => 60,
            'confirm_transfer' => '1',
        ])->assertSessionHasNoErrors();

        UtilityReading::query()->create([
            'room_id' => $newRoom->id, 'contract_id' => $contract->id,
            'month' => 8, 'year' => 2026, 'record_date' => '2026-08-31', 'reading_type' => 'periodic',
            'electricity_old' => 500, 'electricity_new' => 510, 'water_old' => 60, 'water_new' => 62,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);

        $preview = app(InvoiceGenerator::class)->preview($contract->fresh(), 9, 2026);
        $this->assertSame($newRoom->id, $preview['room']->id);
        $effectiveDate = RoomTransfer::query()->where('status', RoomTransfer::STATUS_COMPLETED)->sole()->effective_date;
        $newRoomDays = $effectiveDate->diffInDays($effectiveDate->copy()->endOfMonth()) + 1;
        $this->assertEqualsWithDelta(3600000 * $newRoomDays / $effectiveDate->daysInMonth, $preview['room_fee'], 1);
    }

    public function test_admin_direct_transfer_notifies_tenant_and_unavailable_room_is_rejected(): void
    {
        [$admin, $client, $contract, , $newRoom] = $this->fixture();
        $newRoom->update(['status' => Room::STATUS_OCCUPIED]);

        $this->actingAs($admin)->post(route('admin.room-transfers.store', $contract), [
            'new_room_id' => $newRoom->id,
            'effective_date' => today()->toDateString(),
            'admin_reason' => 'Điều chuyển theo kế hoạch vận hành.',
            'old_electricity' => 100, 'old_water' => 20,
            'new_electricity' => 500, 'new_water' => 60,
            'confirm_transfer' => '1',
        ])->assertSessionHasErrors('new_room_id');
        $this->assertDatabaseCount('room_transfers', 0);
        $this->assertCount(0, $client->notifications);

        $newRoom->update(['status' => Room::STATUS_AVAILABLE]);
        $this->post(route('admin.room-transfers.store', $contract), [
            'new_room_id' => $newRoom->id,
            'effective_date' => today()->toDateString(),
            'admin_reason' => 'Điều chuyển theo kế hoạch vận hành.',
            'old_electricity' => 100, 'old_water' => 20,
            'new_electricity' => 500, 'new_water' => 60,
            'confirm_transfer' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame($newRoom->id, $contract->fresh()->room_id);
        $this->assertSame('room_transfer_completed', $client->notifications()->sole()->data['type']);
    }

    private function fixture(): array
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'room-transfer-admin@example.test', 'role_id' => $adminRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $client = User::create([
            'name' => 'Client', 'email' => 'room-transfer-client@example.test', 'role_id' => $clientRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $client->id, 'full_name' => 'Khách đổi phòng',
            'cccd' => '079000008888', 'phone' => '0900008888',
        ]);
        $oldRoom = Room::create([
            'room_code' => 'OLD-101', 'floor' => 1, 'price' => 3000000, 'area' => 20,
            'max_people' => 2, 'current_people' => 1, 'status' => Room::STATUS_OCCUPIED,
        ]);
        $newRoom = Room::create([
            'room_code' => 'NEW-202', 'floor' => 2, 'price' => 3600000, 'area' => 30,
            'max_people' => 4, 'current_people' => 0, 'status' => Room::STATUS_AVAILABLE,
        ]);
        $asset = Amenity::query()->firstOrCreate(['name' => 'Giường'], [
            'description' => 'Giường gỗ', 'category' => Amenity::CATEGORY_ASSET,
            'is_quantifiable' => true, 'is_active' => true,
        ]);
        $oldRoom->amenities()->attach($asset->id, ['quantity' => 1, 'condition' => 'normal']);
        $newRoom->amenities()->attach($asset->id, ['quantity' => 2, 'condition' => 'normal']);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-ROOM-TRANSFER', 'room_id' => $oldRoom->id, 'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000, 'deposit_amount' => 3000000, 'number_of_people' => 1,
            'start_date' => '2026-07-01', 'end_date' => '2027-07-01', 'actual_move_in_at' => '2026-07-01 09:00:00',
            'signed_at' => '2026-06-30 09:00:00', 'status' => Contract::STATUS_ACTIVE,
        ]);
        $contract->handoverItems()->create([
            'amenity_id' => $asset->id, 'name' => $asset->name, 'description' => $asset->description,
            'is_quantifiable' => true, 'quantity' => 1, 'condition' => 'normal',
        ]);
        UtilityReading::query()->create([
            'room_id' => $oldRoom->id, 'contract_id' => $contract->id,
            'month' => 7, 'year' => 2026, 'record_date' => '2026-07-01', 'reading_type' => 'handover',
            'electricity_old' => 100, 'electricity_new' => 100, 'water_old' => 20, 'water_new' => 20,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);
        UtilityReading::query()->create([
            'room_id' => $newRoom->id, 'contract_id' => null,
            'month' => 7, 'year' => 2026, 'record_date' => '2026-07-31', 'reading_type' => 'baseline',
            'electricity_old' => 500, 'electricity_new' => 500, 'water_old' => 60, 'water_new' => 60,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);

        return [$admin, $client, $contract, $oldRoom, $newRoom, $asset];
    }
}
