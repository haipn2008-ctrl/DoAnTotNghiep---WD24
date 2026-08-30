<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\ContractLifecycleAlert;
use App\Models\ContractTemplate;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Services\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractAppendixWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sends_appendix_tenant_rejects_with_reason_and_accepts_a_revision(): void
    {
        [$admin, $client, $otherClient, $contract] = $this->fixture();
        $payload = [
            'title' => ContractTemplate::CLAUSE_LABELS['tenant_obligations'],
            'legal_basis' => 'Chính sách quản lý cư trú mới có hiệu lực.',
            'content' => 'Bên thuê bổ sung thông tin cư trú trong vòng mười ngày kể từ ngày phụ lục có hiệu lực.',
            'effective_from' => today()->addWeek()->toDateString(),
        ];

        $createPage = $this->actingAs($admin)->get(route('admin.contracts.appendices.create', $contract))
            ->assertOk()
            ->assertSee('name="title"', false)
            ->assertSee('data-appendix-content', false)
            ->assertSee('-- Chọn điều khoản cần sửa đổi/bổ sung --')
            ->assertViewHas('contentDefaults', fn (array $defaults): bool => $defaults['Quyền và nghĩa vụ Bên B'] === ContractTemplate::DEFAULT_CLAUSES['tenant_obligations']);
        foreach (ContractTemplate::CLAUSE_LABELS as $clauseTitle) {
            $createPage->assertSee($clauseTitle);
        }

        $this->post(route('admin.contracts.appendices.store', $contract), array_merge($payload, [
            'title' => 'Tiêu đề nhập tùy ý',
        ]))->assertSessionHasErrors('title');
        $this->assertDatabaseCount('contract_appendices', 0);

        $this->actingAs($admin)->post(route('admin.contracts.appendices.store', $contract), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $appendix = ContractAppendix::query()->sole();
        $this->assertSame(ContractAppendix::STATUS_DRAFT, $appendix->status);
        $this->assertSame('PL-HD-APPENDIX-01-01-R1', $appendix->code);
        $this->assertSame('Quyền và nghĩa vụ Bên B', $appendix->title);
        $this->get(route('admin.contracts.appendices.create', $contract))->assertStatus(409);

        $this->post(route('admin.contract-appendices.send', $appendix))
            ->assertSessionHas('success');
        $appendix->refresh();
        $this->assertSame(ContractAppendix::STATUS_PENDING_TENANT, $appendix->status);
        $this->assertTrue($appendix->hasValidContentHash());
        $clientNotification = $client->notifications()->sole();
        $this->assertSame('contract_appendix_pending', $clientNotification->data['type']);
        $this->actingAs($client)->get(route('client.notifications.open', $clientNotification->id))
            ->assertRedirect(route('client.contract-appendices.show', $appendix));
        $this->assertNotNull($clientNotification->fresh()->read_at);

        $this->actingAs($otherClient)->get(route('client.contract-appendices.show', $appendix))->assertNotFound();
        $this->actingAs($client)->get(route('client.contract-appendices.show', $appendix))
            ->assertOk()->assertSee($appendix->code)->assertSee('Chấp nhận phụ lục')->assertSee('Gửi lý do từ chối');

        $this->post(route('client.contract-appendices.reject', $appendix), [])
            ->assertSessionHasErrors('rejection_reason');
        $this->post(route('client.contract-appendices.reject', $appendix), [
            'rejection_reason' => 'Thời hạn mười ngày chưa phù hợp, đề nghị điều chỉnh thành ba mươi ngày.',
        ])->assertSessionHas('success');
        $appendix->refresh();
        $this->assertSame(ContractAppendix::STATUS_REJECTED, $appendix->status);
        $this->assertDatabaseHas('contract_lifecycle_alerts', [
            'contract_id' => $contract->id,
            'type' => 'contract_appendix_response',
        ]);
        $adminNotification = ContractLifecycleAlert::query()->where('type', 'contract_appendix_response')->sole();
        $this->actingAs($admin)->get(route('admin.notifications.open', $adminNotification))
            ->assertRedirect(route('admin.contract-appendices.show', $appendix));
        $this->assertNotNull($adminNotification->fresh()->resolved_at);

        $this->get(route('admin.contract-appendices.show', $appendix))
            ->assertOk()->assertSee('Thời hạn mười ngày chưa phù hợp')->assertSee('Tạo bản sửa đổi');
        $this->post(route('admin.contract-appendices.revise', $appendix))->assertRedirect();
        $appendix->refresh();
        $revision = ContractAppendix::query()->whereKeyNot($appendix->id)->sole();
        $this->assertSame(ContractAppendix::STATUS_SUPERSEDED, $appendix->status);
        $this->assertSame(ContractAppendix::STATUS_DRAFT, $revision->status);
        $this->assertSame(2, $revision->revision);
        $this->assertSame($appendix->id, $revision->parent_appendix_id);

        $revisedContent = 'Bên thuê bổ sung thông tin cư trú trong vòng ba mươi ngày kể từ ngày phụ lục có hiệu lực.';
        $this->put(route('admin.contract-appendices.update', $revision), array_merge($payload, [
            'content' => $revisedContent,
        ]))->assertSessionHasNoErrors();
        $this->post(route('admin.contract-appendices.send', $revision))->assertSessionHas('success');

        $this->actingAs($client)->post(route('client.contract-appendices.accept', $revision), [])
            ->assertSessionHasErrors('confirmation');
        $this->post(route('client.contract-appendices.accept', $revision), ['confirmation' => '1'])
            ->assertSessionHas('success');
        $revision->refresh();
        $this->assertSame(ContractAppendix::STATUS_ACCEPTED, $revision->status);
        $this->assertTrue($revision->hasValidContentHash());
        $this->assertSame($revisedContent, $revision->content);
        $this->assertSame('Thời hạn mười ngày chưa phù hợp, đề nghị điều chỉnh thành ba mươi ngày.', $appendix->rejection_reason);
    }

    public function test_unsigned_or_terminal_contract_cannot_receive_an_appendix(): void
    {
        [$admin, , , $contract] = $this->fixture();
        $contract->forceFill(['signed_at' => null, 'status' => Contract::STATUS_DRAFT])->save();

        $this->actingAs($admin)->get(route('admin.contracts.appendices.create', $contract))
            ->assertStatus(409);
        $this->post(route('admin.contracts.appendices.store', $contract), [
            'title' => ContractTemplate::CLAUSE_LABELS['effectiveness'],
            'content' => 'Nội dung này không được tạo vì hợp đồng chưa ký.',
            'effective_from' => today()->toDateString(),
        ])->assertSessionHasErrors('contract');
        $this->assertDatabaseCount('contract_appendices', 0);

        $contract->forceFill(['signed_at' => now(), 'status' => Contract::STATUS_COMPLETED])->save();
        $this->get(route('admin.contracts.appendices.create', $contract))->assertStatus(409);
        $this->post(route('admin.contracts.appendices.store', $contract), [
            'title' => ContractTemplate::CLAUSE_LABELS['settlement'],
            'content' => 'Nội dung này không được tạo vì hợp đồng đã hoàn tất toàn bộ nghĩa vụ.',
            'effective_from' => today()->toDateString(),
        ])->assertSessionHasErrors('contract');
        $this->assertDatabaseCount('contract_appendices', 0);
    }

    public function test_accepted_price_appendix_changes_only_future_service_period_rates(): void
    {
        [$admin, $client, , $contract] = $this->fixture();
        $contract->forceFill([
            'electric_price_snapshot' => 4000,
            'water_price_snapshot' => 15000,
            'internet_fee_snapshot' => 100000,
            'service_fee_snapshot' => 50000,
        ])->save();

        $effectiveFrom = today()->addMonthNoOverflow()->day(15);
        $servicePeriod = $effectiveFrom->copy()->addMonthNoOverflow()->startOfMonth();
        UtilityReading::create([
            'room_id' => $contract->room_id,
            'contract_id' => $contract->id,
            'month' => $servicePeriod->month,
            'year' => $servicePeriod->year,
            'reading_type' => 'periodic',
            'record_date' => $servicePeriod->copy()->endOfMonth(),
            'electricity_old' => 100,
            'electricity_new' => 110,
            'water_old' => 20,
            'water_new' => 22,
            'status' => UtilityReading::STATUS_CONFIRMED,
        ]);

        $this->actingAs($admin)->get(route('admin.contracts.appendices.create', $contract))
            ->assertOk()
            ->assertSee('Điều chỉnh giá điện')
            ->assertSee('Điều chỉnh nhiều đơn giá dịch vụ')
            ->assertSee('data-price-adjustment-panel', false)
            ->assertViewHas('contentDefaults', fn (array $defaults): bool => str_contains($defaults['Điều chỉnh giá điện'], '4.000 đ/kWh'));

        $this->post(route('admin.contracts.appendices.store', $contract), [
            'title' => 'Điều chỉnh giá điện',
            'legal_basis' => 'Điều chỉnh theo thỏa thuận mới giữa hai bên.',
            'content' => 'Hai bên thống nhất thay đổi đơn giá điện áp dụng cho các kỳ dịch vụ tiếp theo.',
            'effective_from' => $effectiveFrom->toDateString(),
            'price_adjustments' => ['electric_price' => 4500],
        ])->assertSessionHasNoErrors();

        $appendix = ContractAppendix::query()->sole();
        $this->assertSame(4000.0, (float) $appendix->price_adjustments['electric_price']['old']);
        $this->assertSame(4500.0, (float) $appendix->price_adjustments['electric_price']['new']);
        $this->get(route('admin.contract-appendices.show', $appendix))
            ->assertOk()
            ->assertSee('4.000 đ/kWh')
            ->assertSee('4.500 đ/kWh')
            ->assertSee('kỳ dịch vụ bắt đầu sau ngày hiệu lực');

        $billingPeriod = $servicePeriod->copy()->addMonthNoOverflow();
        $beforeAcceptance = app(InvoiceGenerator::class)->preview($contract, $billingPeriod->month, $billingPeriod->year);
        $this->assertSame(4000.0, collect($beforeAcceptance['lines'])->firstWhere('type', 'electricity')['unit_price']);

        $this->post(route('admin.contract-appendices.send', $appendix))->assertSessionHasNoErrors();
        $this->actingAs($client)->post(route('client.contract-appendices.accept', $appendix), [
            'confirmation' => 1,
        ])->assertSessionHasNoErrors();

        $afterAcceptance = app(InvoiceGenerator::class)->preview($contract->fresh(), $billingPeriod->month, $billingPeriod->year);
        $this->assertSame(4500.0, collect($afterAcceptance['lines'])->firstWhere('type', 'electricity')['unit_price']);
        $this->assertSame(15000.0, collect($afterAcceptance['lines'])->firstWhere('type', 'water')['unit_price']);
    }

    private function fixture(): array
    {
        $adminRole = Role::create(['role_name' => 'Admin']);
        $clientRole = Role::create(['role_name' => 'User']);
        $admin = User::create(['name' => 'Admin', 'email' => 'appendix-admin@example.test', 'role_id' => $adminRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $client = User::create(['name' => 'Client', 'email' => 'appendix-client@example.test', 'role_id' => $clientRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $otherClient = User::create(['name' => 'Other', 'email' => 'appendix-other@example.test', 'role_id' => $clientRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE]);
        $tenant = Tenant::create(['user_id' => $client->id, 'full_name' => 'Khách phụ lục', 'cccd' => '079000001111', 'phone' => '0900001111']);
        $room = Room::create(['room_code' => 'APPENDIX-01', 'floor' => 1, 'price' => 2500000, 'area' => 20, 'max_people' => 2, 'current_people' => 1, 'status' => Room::STATUS_OCCUPIED]);
        $contract = Contract::query()->forceCreate([
            'contract_code' => 'HD-APPENDIX-01', 'room_id' => $room->id, 'tenant_id' => $tenant->id,
            'monthly_rent' => 2500000, 'deposit_amount' => 2500000, 'number_of_people' => 1,
            'start_date' => today()->subMonth(), 'end_date' => today()->addYear(),
            'signed_at' => now()->subMonth(), 'actual_move_in_at' => now()->subMonth(),
            'status' => Contract::STATUS_ACTIVE,
        ]);

        return [$admin, $client, $otherClient, $contract];
    }
}
