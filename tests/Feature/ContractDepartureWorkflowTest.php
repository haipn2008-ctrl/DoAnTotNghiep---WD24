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
            'admin_note' => 'Có mặt trước 15 phút để kiểm kê tài sản.',
        ])->assertSessionHas('success');

        $contract->refresh();
        $departureRequest->refresh();
        $this->assertSame(ContractTerminationRequest::STATUS_APPROVED, $departureRequest->status);
        $this->assertSame($departureRequest->id, $contract->approved_termination_request_id);
        $this->assertSame($departureDate.' 08:00:00', $contract->scheduled_move_out_at->format('Y-m-d H:i:s'));
        $this->actingAs($admin)->get(route('admin.contracts.check-out.form', $contract))
            ->assertSuccessful()
            ->assertDontSee('name="scheduled_checkout_at"', false)
            ->assertSee('Giờ hành chính 08:00–17:00')
            ->assertSee('class="mt-3 hidden text-xs font-semibold text-violet-900" data-schedule-variance-field', false);
        $this->actingAs($client)->get(route('client.contracts.show', $contract))
            ->assertSuccessful()
            ->assertDontSee('Lịch bàn giao đã được xác nhận')
            ->assertSeeInOrder([
                'Thời hạn hợp đồng',
                'Lịch kết thúc và bàn giao phòng',
                'Thông tin nhận phòng',
            ])
            ->assertSee(\Carbon\Carbon::parse($departureDate)->format('d/m/Y'))
            ->assertSee('Trong giờ hành chính 08:00–17:00')
            ->assertDontSee('10:00 '.\Carbon\Carbon::parse($departureDate)->format('d/m/Y'))
            ->assertSee('Có mặt trước 15 phút để kiểm kê tài sản.');

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
                'has_damage' => 0,
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
        $this->actingAs($admin)->get(route('admin.termination-requests.index'))
            ->assertSuccessful()
            ->assertSee('Đã trả phòng');
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

    public function test_admin_can_cancel_an_approved_departure_before_checkout_so_the_contract_can_be_extended(): void
    {
        [$admin, , $contract] = $this->fixture();
        $departureRequest = ContractTerminationRequest::create([
            'contract_id' => $contract->id,
            'tenant_id' => $contract->tenant_id,
            'requested_end_date' => $contract->end_date->toDateString(),
            'reason' => 'Khách dự kiến trả phòng khi hết hạn.',
            'request_type' => ContractTerminationRequest::TYPE_END_OF_TERM,
            'status' => ContractTerminationRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->post(route('admin.termination-requests.approve', $departureRequest), [
            'approved_end_date' => $contract->end_date->toDateString(),
        ])->assertSessionHas('success');

        $this->post(route('admin.termination-requests.cancel', $departureRequest), [
            'cancel_reason' => 'Hai bên thống nhất tiếp tục thuê và làm thủ tục gia hạn.',
        ])->assertSessionHas('success');

        $contract->refresh();
        $this->assertSame(ContractTerminationRequest::STATUS_CANCELLED, $departureRequest->fresh()->status);
        $this->assertNull($contract->scheduled_move_out_at);
        $this->assertNull($contract->approved_termination_request_id);
        $this->assertDatabaseHas('contract_histories', [
            'contract_id' => $contract->id,
            'action' => 'termination_cancelled',
        ]);
        $this->assertContains('termination_schedule_cancelled', $contract->tenant->user->notifications()->get()->pluck('data.type')->all());
    }

    public function test_admin_can_approve_a_same_day_departure_without_a_specific_time(): void
    {
        [$admin, , $contract] = $this->fixture();
        $departureRequest = ContractTerminationRequest::create([
            'contract_id' => $contract->id,
            'tenant_id' => $contract->tenant_id,
            'requested_end_date' => today()->toDateString(),
            'reason' => 'Đã hoàn tất bàn giao phòng trong hôm nay.',
            'request_type' => ContractTerminationRequest::TYPE_END_OF_TERM,
            'status' => ContractTerminationRequest::STATUS_PENDING,
        ]);

        Carbon::setTestNow(today()->setTime(23, 59));

        try {
            $this->actingAs($admin)->post(route('admin.termination-requests.approve', $departureRequest), [
                'approved_end_date' => today()->toDateString(),
            ])->assertSessionHas('success');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(ContractTerminationRequest::STATUS_APPROVED, $departureRequest->fresh()->status);
        $this->assertSame('08:00:00', $departureRequest->fresh()->scheduled_checkout_at->format('H:i:s'));
    }

    public function test_checkout_requires_a_reason_only_when_actual_handover_moves_to_another_day(): void
    {
        Carbon::setTestNow('2026-08-29 12:00:00');
        try {
            [$admin, , $contract] = $this->fixture();
            $departureRequest = ContractTerminationRequest::create([
                'contract_id' => $contract->id,
                'tenant_id' => $contract->tenant_id,
                'requested_end_date' => today()->toDateString(),
                'reason' => 'Bàn giao trong hôm nay.',
                'request_type' => ContractTerminationRequest::TYPE_EARLY_TERMINATION,
                'status' => ContractTerminationRequest::STATUS_PENDING,
            ]);
            app(ContractLifecycleService::class)->scheduleDeparture(
                $departureRequest,
                $admin,
                today(),
                today()->setTime(8, 0),
            );
            Carbon::setTestNow('2026-08-30 12:00:00');
            $payload = [
                'actual_move_out_at' => now()->format('Y-m-d H:i:s'),
                'checkout_electricity' => 125,
                'checkout_water' => 18,
                'checkout_reason' => 'Đã hoàn tất bàn giao.',
                'has_damage' => 0,
                'handover_confirmed' => '1',
            ];

            $this->actingAs($admin)->post(route('admin.contracts.check-out', $contract), $payload)
                ->assertSessionHasErrors('schedule_variance_reason');
            $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);

            $this->post(route('admin.contracts.check-out', $contract), $payload + [
                'schedule_variance_reason' => 'Khách chuyển lịch bàn giao sang ngày hôm sau.',
            ])->assertSessionHas('success');
            $this->assertSame(Contract::STATUS_SETTLING, $contract->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_checkout_must_take_place_during_business_hours(): void
    {
        Carbon::setTestNow('2026-08-29 18:00:00');
        try {
            [$admin, , $contract] = $this->fixture();

            $this->actingAs($admin)->post(route('admin.contracts.check-out', $contract), [
                'actual_move_out_at' => today()->setTime(17, 1)->format('Y-m-d H:i:s'),
                'checkout_electricity' => 125,
                'checkout_water' => 18,
                'checkout_reason' => 'Bàn giao ngoài giờ hành chính.',
                'has_damage' => 0,
                'handover_confirmed' => '1',
            ])->assertSessionHasErrors('actual_move_out_at');

            $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_unified_admin_workflow_shows_the_admin_departure_reason_to_the_client(): void
    {
        [$admin, $client, $contract] = $this->fixture();
        ContractTerminationRequest::create([
            'contract_id' => $contract->id,
            'tenant_id' => $contract->tenant_id,
            'requested_end_date' => today()->toDateString(),
            'reason' => 'Lý do cũ từ yêu cầu đang chờ.',
            'request_type' => ContractTerminationRequest::TYPE_EARLY_TERMINATION,
            'status' => ContractTerminationRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->post(route('admin.contracts.departure-schedule', $contract), [
            'approved_end_date' => today()->toDateString(),
            'departure_reason' => 'Hai bên thống nhất kết thúc hợp đồng đúng hạn.',
            'admin_note' => 'Chuẩn bị chìa khóa để bàn giao.',
        ])->assertSessionHas('success');

        $departureRequest = ContractTerminationRequest::query()->sole();
        $this->assertSame('Hai bên thống nhất kết thúc hợp đồng đúng hạn.', $departureRequest->reason);
        $this->assertSame('Chuẩn bị chìa khóa để bàn giao.', $departureRequest->admin_note);
        $this->actingAs($client)->get(route('client.contracts.show', $contract))
            ->assertSuccessful()
            ->assertSee('Hai bên thống nhất kết thúc hợp đồng đúng hạn.')
            ->assertSee('Chuẩn bị chìa khóa để bàn giao.')
            ->assertDontSee('Lý do cũ từ yêu cầu đang chờ.');
    }

    public function test_legacy_termination_routes_only_redirect_to_the_unified_workflow(): void
    {
        [$admin, , $contract] = $this->fixture();

        $this->actingAs($admin)->get(route('admin.contracts.end.form', $contract))
            ->assertRedirect(route('admin.contracts.check-out.form', $contract));
        $this->post(route('admin.contracts.terminate', $contract), [
            'actual_end_date' => today()->toDateString(),
            'termination_reason' => 'legacy-form',
        ])->assertRedirect(route('admin.contracts.check-out.form', $contract));

        $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $contract->room->fresh()->status);
        $this->assertDatabaseCount('contract_termination_requests', 0);
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
