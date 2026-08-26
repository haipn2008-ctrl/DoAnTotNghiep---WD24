<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractLifecycleAlert;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractExtensionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_occupancy_can_request_and_receive_a_safe_extension(): void
    {
        [$admin, $client, $contract] = $this->fixture(Contract::STATUS_EXPIRED);
        $oldEndDate = $contract->end_date->copy();
        $requestedEndDate = today()->addMonths(2)->toDateString();

        $this->actingAs($client)->post(route('client.extension-requests.store'), [
            'contract_id' => $contract->id,
            'requested_end_date' => $requestedEndDate,
            'reason' => 'Tiếp tục thuê phòng.',
        ])->assertRedirect(route('client.extension-requests.index'));

        $extensionRequest = ContractExtensionRequest::query()->sole();
        $this->assertSame(ContractExtensionRequest::STATUS_PENDING, $extensionRequest->status);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'contract_id' => $contract->id,
            'type' => 'extension_request',
            'resolved_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.extension-requests.approve', $extensionRequest), [
                'approved_end_date' => $requestedEndDate,
                'proposed_monthly_rent' => 2700000,
            ])
            ->assertSessionHasErrors('extension_agreed');

        $this->assertSame(ContractExtensionRequest::STATUS_PENDING, $extensionRequest->fresh()->status);
        $this->assertSame($oldEndDate->toDateString(), $contract->fresh()->end_date->toDateString());

        $this->post(route('admin.extension-requests.approve', $extensionRequest), [
            'approved_end_date' => $requestedEndDate,
            'proposed_monthly_rent' => 2700000,
            'extension_agreed' => '1',
        ])
            ->assertSessionHas('success');

        $contract->refresh();
        $extensionRequest->refresh();
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->status);
        $this->assertSame($requestedEndDate, $contract->end_date->toDateString());
        $this->assertSame($oldEndDate->copy()->addDay()->toDateString(), $contract->extend_start_date->toDateString());
        $this->assertSame('2700000.00', $contract->monthly_rent);
        $this->assertSame(ContractExtensionRequest::STATUS_APPROVED, $extensionRequest->status);
        $this->assertNotNull($extensionRequest->processed_at);
        $this->assertNotNull(ContractLifecycleAlert::query()->where('type', 'extension_request')->sole()->resolved_at);
        $this->assertContains('extension_request_approved', $client->notifications()->get()->pluck('data.type')->all());
        $this->assertDatabaseMissing('contract_lifecycle_alerts', [
            'contract_id' => $contract->id,
            'type' => 'extension_response',
        ]);
    }

    public function test_rejection_requires_and_preserves_admin_reason(): void
    {
        [$admin, $client, $contract] = $this->fixture();
        $request = ContractExtensionRequest::create([
            'contract_id' => $contract->id,
            'current_end_date' => $contract->end_date,
            'requested_end_date' => $contract->end_date->copy()->addMonth(),
            'reason' => 'Muốn thuê tiếp.',
            'status' => ContractExtensionRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.extension-requests.reject', $request))
            ->assertSessionHasErrors('reject_reason');
        $this->assertSame(ContractExtensionRequest::STATUS_PENDING, $request->fresh()->status);

        $this->post(route('admin.extension-requests.reject', $request), [
            'reject_reason' => 'Phòng đã có kế hoạch bảo trì dài hạn.',
        ])->assertSessionHas('success');

        $request->refresh();
        $this->assertSame(ContractExtensionRequest::STATUS_REJECTED, $request->status);
        $this->assertSame('Phòng đã có kế hoạch bảo trì dài hạn.', $request->admin_note);
        $this->assertNotNull($request->processed_at);
        $this->assertSame('extension_request_rejected', $client->notifications()->sole()->data['type']);
    }

    public function test_admin_cannot_offer_extension_with_debt_without_an_explicit_exception(): void
    {
        [$admin, , $contract] = $this->fixture();
        Invoice::query()->forceCreate([
            'invoice_code' => 'INV-EXT-DEBT',
            'contract_id' => $contract->id,
            'room_id' => $contract->room_id,
            'invoice_type' => Invoice::TYPE_RENTAL,
            'month' => today()->month,
            'year' => today()->year,
            'invoice_date' => today(),
            'due_date' => today(),
            'room_fee' => 500000,
            'total_amount' => 500000,
            'status' => Invoice::STATUS_UNPAID,
        ]);
        $payload = [
            'new_end_date' => $contract->end_date->copy()->addYear()->toDateString(),
            'proposed_monthly_rent' => 2600000,
            'reason' => 'Tiếp tục thuê thêm một năm.',
            'confirm_extend' => '1',
        ];

        $payloadWithoutAgreement = $payload;
        unset($payloadWithoutAgreement['confirm_extend']);
        $this->actingAs($admin)->post(route('admin.contracts.extend', $contract), $payloadWithoutAgreement)
            ->assertSessionHasErrors('confirm_extend');
        $this->assertDatabaseCount('contract_extension_requests', 0);

        $this->post(route('admin.contracts.extend', $contract), $payload)
            ->assertSessionHasErrors('financial_override_reason');
        $this->assertDatabaseCount('contract_extension_requests', 0);

        $this->post(route('admin.contracts.extend', $contract), $payload + [
            'financial_override_reason' => 'Đã duyệt kế hoạch thanh toán phần nợ còn lại trong tuần này.',
        ])->assertSessionHas('success');

        $extensionRequest = ContractExtensionRequest::query()->sole();
        $this->assertSame(ContractExtensionRequest::STATUS_APPROVED, $extensionRequest->status);
        $this->assertSame(500000.0, (float) $extensionRequest->terms_snapshot['outstanding_at_offer']);
        $this->assertSame($payload['new_end_date'], $contract->fresh()->end_date->toDateString());
    }

    public function test_admin_can_finalize_a_legacy_extension_that_was_waiting_for_tenant_confirmation(): void
    {
        [$admin, , $contract] = $this->fixture();
        $newEndDate = $contract->end_date->copy()->addYear()->toDateString();
        $legacyRequest = ContractExtensionRequest::create([
            'contract_id' => $contract->id,
            'current_end_date' => $contract->end_date,
            'requested_end_date' => $newEndDate,
            'approved_end_date' => $newEndDate,
            'proposed_monthly_rent' => 2800000,
            'reason' => 'Phụ lục cũ đang chờ khách xác nhận.',
            'status' => ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION,
        ]);

        $this->actingAs($admin)->post(route('admin.contracts.extend', $contract), [
            'new_end_date' => $newEndDate,
            'proposed_monthly_rent' => 2800000,
            'reason' => 'Hai bên đã thống nhất trực tiếp.',
            'confirm_extend' => '1',
        ])->assertRedirect(route('admin.contracts.show', $contract))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('contract_extension_requests', 1);
        $this->assertSame(ContractExtensionRequest::STATUS_APPROVED, $legacyRequest->fresh()->status);
        $this->assertSame($newEndDate, $contract->fresh()->end_date->toDateString());
        $this->assertSame('2800000.00', $contract->fresh()->monthly_rent);
    }

    private function fixture(string $status = Contract::STATUS_ACTIVE): array
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'extension-admin@example.test', 'role_id' => $adminRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $client = User::create([
            'name' => 'Client', 'email' => 'extension-client@example.test', 'role_id' => $clientRole->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $client->id, 'full_name' => 'Khách gia hạn',
            'cccd' => '079000007777', 'phone' => '0900007777',
        ]);
        $room = Room::create([
            'room_code' => 'EXTEND-01', 'floor' => 1, 'price' => 2500000,
            'area' => 20, 'max_people' => 2, 'current_people' => 1,
            'status' => Room::STATUS_OCCUPIED,
        ]);
        $endDate = $status === Contract::STATUS_EXPIRED ? today()->subDay() : today()->addMonth();
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-EXTEND-01', 'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'monthly_rent' => 2500000, 'deposit_amount' => 2500000, 'number_of_people' => 1,
            'start_date' => today()->subYear(), 'end_date' => $endDate,
            'actual_move_in_at' => now()->subYear(), 'status' => $status,
        ]);

        return [$admin, $client, $contract];
    }
}
