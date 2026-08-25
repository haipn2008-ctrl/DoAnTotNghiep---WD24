<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractTerminationRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Services\ContractLifecycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractDepartureWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_of_term_request_becomes_schedule_and_is_fulfilled_by_checkout(): void
    {
        [$admin, $client, $contract] = $this->fixture();
        $departureDate = $contract->end_date->toDateString();
        $checkoutAt = $departureDate.' 10:00:00';

        $this->actingAs($client)->post(route('client.termination-requests.store'), [
            'contract_id' => $contract->id,
            'requested_end_date' => $departureDate,
            'reason' => 'Tôi sẽ bàn giao phòng khi hợp đồng hết hạn.',
        ])->assertRedirect(route('client.termination-requests.index'));

        $departureRequest = ContractTerminationRequest::query()->sole();
        $this->assertSame(ContractTerminationRequest::TYPE_END_OF_TERM, $departureRequest->request_type);

        $this->actingAs($admin)->post(route('admin.termination-requests.approve', $departureRequest), [
            'approved_end_date' => $departureDate,
            'scheduled_checkout_at' => $checkoutAt,
            'admin_note' => 'Có mặt trước 15 phút để kiểm kê tài sản.',
        ])->assertSessionHas('success');

        $contract->refresh();
        $departureRequest->refresh();
        $this->assertSame(ContractTerminationRequest::STATUS_APPROVED, $departureRequest->status);
        $this->assertSame($departureRequest->id, $contract->approved_termination_request_id);
        $this->assertSame($checkoutAt, $contract->scheduled_move_out_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow(Carbon::parse($checkoutAt));
        try {
            app(ContractLifecycleService::class)->processDailyAlerts();
            $this->assertDatabaseHas('contract_lifecycle_alerts', [
                'contract_id' => $contract->id,
                'type' => 'departure_due',
                'resolved_at' => null,
            ]);

            $this->actingAs($admin)->post(route('admin.contracts.check-out', $contract), [
                'actual_move_out_at' => $checkoutAt,
                'checkout_electricity' => 125,
                'checkout_water' => 18,
                'checkout_reason' => 'Bàn giao đúng lịch đã duyệt.',
                'checkout_key_count' => 1,
                'handover_confirmed' => '1',
            ])->assertSessionHas('success');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(Contract::STATUS_SETTLING, $contract->fresh()->status);
        $this->assertSame(ContractTerminationRequest::STATUS_COMPLETED, $departureRequest->fresh()->status);
        $this->assertNotNull($departureRequest->fresh()->fulfilled_at);
        $this->assertNotNull(ContractLifecycleAlert::query()->where('type', 'departure_due')->sole()->resolved_at);
    }

    public function test_request_type_distinguishes_early_and_overdue_departure(): void
    {
        [, $client, $contract] = $this->fixture();
        $this->actingAs($client)->post(route('client.termination-requests.store'), [
            'contract_id' => $contract->id,
            'requested_end_date' => today()->addDay()->toDateString(),
            'reason' => 'Cần chuyển nơi ở trước hạn.',
        ])->assertSessionHas('success');
        $this->assertSame(
            ContractTerminationRequest::TYPE_EARLY_TERMINATION,
            ContractTerminationRequest::query()->sole()->request_type
        );

        $contract->terminationRequests()->delete();
        $contract->forceFill(['status' => Contract::STATUS_EXPIRED, 'end_date' => today()->subDay()])->save();
        $this->post(route('client.termination-requests.store'), [
            'contract_id' => $contract->id,
            'requested_end_date' => today()->addDay()->toDateString(),
            'reason' => 'Xin bàn giao sau khi hợp đồng đã quá hạn.',
        ])->assertSessionHas('success');
        $this->assertSame(
            ContractTerminationRequest::TYPE_OVERDUE_DEPARTURE,
            ContractTerminationRequest::query()->sole()->request_type
        );
    }

    private function fixture(): array
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'departure-admin@example.test', 'role_id' => $adminRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $client = User::create([
            'name' => 'Client', 'email' => 'departure-client@example.test', 'role_id' => $clientRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $client->id, 'full_name' => 'Khách rời phòng',
            'cccd' => '079000006666', 'phone' => '0900006666',
        ]);
        $room = Room::create([
            'room_code' => 'LEAVE-01', 'floor' => 1, 'price' => 3000000,
            'area' => 24, 'max_people' => 2, 'current_people' => 1,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-LEAVE-01', 'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000, 'deposit_amount' => 0,
            'deposit_status' => Contract::DEPOSIT_NOT_REQUIRED, 'deposit_resolution' => Contract::DEPOSIT_NOT_REQUIRED,
            'number_of_people' => 1, 'start_date' => today()->subYear(), 'end_date' => today()->addDays(7),
            'actual_move_in_at' => now()->subYear(), 'status' => Contract::STATUS_ACTIVE,
        ]);
        UtilityReading::query()->forceCreate([
            'room_id' => $room->id, 'contract_id' => $contract->id,
            'month' => today()->month, 'year' => today()->year, 'record_date' => today()->toDateString(),
            'reading_type' => 'periodic', 'electricity_old' => 100, 'electricity_new' => 120,
            'water_old' => 10, 'water_new' => 15, 'status' => UtilityReading::STATUS_CONFIRMED,
        ]);

        return [$admin, $client, $contract];
    }
}
