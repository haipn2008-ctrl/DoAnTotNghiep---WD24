<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Services\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $adminRole = Role::create(['role_name' => 'Admin']);
        $this->clientRole = Role::create(['role_name' => 'User']);
        $this->admin = $this->user($adminRole, 'invoice-admin@example.test');
        Setting::create(['electric_price' => 3500, 'water_price' => 20000,
            'internet_fee' => 100000, 'service_fee' => 50000, 'parking_fee' => 75000,
            'invoice_day' => 31, 'payment_due_days' => 10]);
    }

    public function test_only_admin_can_access_invoice_pages_preview_issue_and_direct_delete(): void
    {
        [$contract, , , $reading] = $this->fixture('AUTH');
        $invoice = $this->issue($contract);
        $this->get('/admin/invoices')->assertRedirect('/login');
        $client = $this->user($this->clientRole, 'invoice-client@example.test');
        $this->actingAs($client)->get('/admin/invoices')->assertForbidden();
        $this->get("/admin/invoices/contracts/{$contract->id}/preview?month=7&year=2026")->assertForbidden();
        $this->post("/admin/invoices/contracts/{$contract->id}/issue", ['month' => 7, 'year' => 2026])->assertForbidden();
        $this->delete("/admin/invoices/{$invoice->id}")->assertForbidden();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'utility_reading_id' => $reading->id]);
    }

    public function test_admin_can_list_search_filter_show_and_print_with_matching_summary(): void
    {
        [$firstContract, $firstRoom, $firstTenant] = $this->fixture('LIST-A');
        $first = $this->issue($firstContract);
        [$secondContract] = $this->fixture('LIST-B', 8);
        $second = $this->issue($secondContract, 8);
        $this->actingAs($this->admin)->get('/admin/invoices?month=7&year=2026&status=unpaid&keyword='.urlencode($firstTenant->full_name))
            ->assertOk()->assertSee($first->invoice_code)->assertSee($firstRoom->room_code)
            ->assertDontSee($second->invoice_code)->assertViewHas('summary', fn ($summary) => $summary['count'] === 1);
        $this->get("/admin/invoices/{$first->id}")->assertOk()->assertSee($firstTenant->full_name);
        $this->get("/admin/invoices/{$first->id}/print")->assertOk()->assertSee($firstRoom->room_code);
        $this->get('/admin/invoices?month=13&year=1999&status=fake')->assertSessionHasErrors(['month', 'year', 'status']);
        $this->get('/admin/invoices/generate?month=0')->assertSessionHasErrors('month');
        $this->get('/admin/invoices/999999')->assertNotFound();
    }

    public function test_preview_calculates_all_lines_dates_and_total_without_writing(): void
    {
        [$contract] = $this->fixture('PREVIEW');
        $response = $this->actingAs($this->admin)
            ->getJson("/admin/invoices/contracts/{$contract->id}/preview?month=7&year=2026")
            ->assertOk()->assertJsonPath('invoice_date', '2026-07-31')
            ->assertJsonPath('due_date', '2026-08-10')
            ->assertJsonPath('total_amount', 3395000);
        $this->assertSame(['room', 'electricity', 'water', 'internet', 'service', 'parking'],
            collect($response->json('lines'))->pluck('type')->all());
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_details', 0);
    }

    public function test_issue_creates_immutable_snapshot_details_and_concurrency_safe_code_once(): void
    {
        [$contract, , , $reading] = $this->fixture('ISSUE');
        $response = $this->actingAs($this->admin)->postJson("/admin/invoices/contracts/{$contract->id}/issue", [
            'month' => 7, 'year' => 2026,
        ])->assertOk()->assertJson(['success' => true]);
        $invoice = Invoice::findOrFail($response->json('invoice_id'));
        $this->assertSame(sprintf('INV-202607-%06d', $invoice->id), $invoice->invoice_code);
        $this->assertSame($reading->id, $invoice->utility_reading_id);
        $this->assertSame('3395000.00', $invoice->total_amount);
        $this->assertDatabaseCount('invoice_details', 6);

        Setting::first()->update(['electric_price' => 999999]);
        $reading->update(['electricity_new' => 120]);
        $this->assertSame('3395000.00', $invoice->fresh()->total_amount);
        $this->assertSame('70000.00', $invoice->fresh()->electricity_fee);

        $this->postJson("/admin/invoices/contracts/{$contract->id}/issue", ['month' => 7, 'year' => 2026])
            ->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_details', 6);
    }

    public function test_missing_or_unconfirmed_reading_and_contract_outside_period_are_rejected(): void
    {
        [$contract, , , $reading] = $this->fixture('INVALID');
        $reading->delete();
        $this->actingAs($this->admin)->getJson("/admin/invoices/contracts/{$contract->id}/preview?month=7&year=2026")
            ->assertStatus(422)->assertJsonFragment(['message' => 'Phòng ROOM-INVALID chưa chốt điện nước tháng 7/2026.']);
        $reading = $this->reading($contract->room, 7, 'draft');
        $this->postJson("/admin/invoices/contracts/{$contract->id}/issue", ['month' => 7, 'year' => 2026])->assertStatus(422);
        $reading->update(['status' => 'confirmed']);
        $contract->update(['start_date' => '2026-08-01']);
        $this->post('/admin/invoices/generate', ['contract_id' => $contract->id, 'month' => 7, 'year' => 2026])
            ->assertSessionHasErrors('contract');
        $contract->update([
            'start_date' => '2026-01-01',
            'actual_end_date' => '2026-06-30',
            'status' => Contract::STATUS_TERMINATED,
        ]);
        $this->postJson("/admin/invoices/contracts/{$contract->id}/issue", ['month' => 7, 'year' => 2026])->assertStatus(422);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_terminated_and_active_contracts_in_same_room_and_month_can_receive_separate_invoices(): void
    {
        [$newContract, $room, $tenant] = $this->fixture('SAME-ROOM-NEW', 8);
        $oldContract = Contract::create([
            'contract_code' => 'CONTRACT-SAME-ROOM-OLD',
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-10',
            'actual_end_date' => '2026-08-10',
            'status' => Contract::STATUS_TERMINATED,
        ]);
        UtilityReading::create([
            'room_id' => $room->id, 'contract_id' => $oldContract->id,
            'month' => 8, 'year' => 2026, 'record_date' => '2026-08-10',
            'reading_type' => 'periodic', 'electricity_old' => 100, 'electricity_new' => 110,
            'water_old' => 20, 'water_new' => 22, 'status' => 'confirmed',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/invoices/generate?month=8&year=2026')
            ->assertOk()
            ->assertSee($oldContract->contract_code)
            ->assertSee($newContract->contract_code);

        $oldInvoice = app(InvoiceGenerator::class)->issue($oldContract, 8, 2026);
        $newInvoice = app(InvoiceGenerator::class)->issue($newContract, 8, 2026);

        $this->assertNotSame($oldInvoice->id, $newInvoice->id);
        $this->assertDatabaseHas('invoices', [
            'contract_id' => $oldContract->id, 'room_id' => $room->id, 'month' => 8, 'year' => 2026,
        ]);
        $this->assertDatabaseHas('invoices', [
            'contract_id' => $newContract->id, 'room_id' => $room->id, 'month' => 8, 'year' => 2026,
        ]);
    }

    public function test_different_rooms_in_same_period_receive_unique_deterministic_codes(): void
    {
        [$firstContract] = $this->fixture('CODE-A');
        [$secondContract] = $this->fixture('CODE-B');
        $first = $this->issue($firstContract);
        $second = $this->issue($secondContract);
        $this->assertNotSame($first->invoice_code, $second->invoice_code);
        $this->assertSame(sprintf('INV-202607-%06d', $first->id), $first->invoice_code);
        $this->assertSame(sprintf('INV-202607-%06d', $second->id), $second->invoice_code);
        $this->assertDatabaseCount('invoices', 2);
        $this->assertDatabaseCount('invoice_details', 12);
    }

    public function test_issuing_final_invoice_moves_inactive_former_tenant_to_settling(): void
    {
        [$contract, , $tenant] = $this->fixture('FINAL-INVOICE');
        $contract->update([
            'actual_end_date' => '2026-07-10',
            'status' => Contract::STATUS_TERMINATED,
        ]);
        $tenant->user->update(['status' => User::STATUS_INACTIVE]);

        $invoice = $this->issue($contract);

        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status);
        $this->assertSame(User::STATUS_SETTLING, $tenant->user->fresh()->status);
    }

    public function test_previous_tenant_invoice_does_not_disable_new_contract_in_same_room_and_month(): void
    {
        [$newContract, $room, $tenant] = $this->fixture('TURNOVER-INVOICE');
        $oldContract = Contract::create([
            'contract_code' => 'CONTRACT-OLD-TURNOVER', 'room_id' => $room->id,
            'tenant_id' => $tenant->id, 'monthly_rent' => 3000000,
            'start_date' => '2026-01-01', 'end_date' => '2026-07-10',
            'actual_end_date' => '2026-07-10', 'status' => Contract::STATUS_TERMINATED,
        ]);
        Invoice::create([
            'contract_id' => $oldContract->id, 'room_id' => $room->id,
            'invoice_code' => 'INV-202607-800001', 'month' => 7, 'year' => 2026,
            'invoice_date' => '2026-07-10', 'due_date' => '2026-07-20',
            'room_fee' => 3000000, 'total_amount' => 3000000, 'status' => Invoice::STATUS_PAID,
        ]);

        $this->actingAs($this->admin)->get('/admin/invoices/generate?month=7&year=2026')
            ->assertOk()
            ->assertViewHas('issuedContractIds', fn ($ids) =>
                in_array($oldContract->id, $ids) && ! in_array($newContract->id, $ids)
            );
    }

    public function test_update_request_cannot_mutate_financial_snapshot(): void
    {
        [$contract] = $this->fixture('IMMUTABLE');
        $invoice = $this->issue($contract);
        $before = $invoice->only(['total_amount', 'room_fee', 'status', 'month', 'year']);
        $this->actingAs($this->admin)->put("/admin/invoices/{$invoice->id}", [
            'total_amount' => 1, 'room_fee' => 1, 'status' => 'paid', 'month' => 1, 'year' => 2000,
        ])->assertRedirect(route('admin.invoices.show', $invoice))->assertSessionHas('error');
        $this->assertSame($before, $invoice->fresh()->only(array_keys($before)));
    }

    public function test_delete_preserves_invoice_with_any_payment_but_removes_empty_invoice_details_only_once(): void
    {
        [$paidContract] = $this->fixture('DELETE-PAID');
        $withPayment = $this->issue($paidContract);
        Payment::create(['invoice_id' => $withPayment->id, 'amount_paid' => 1, 'payment_date' => '2026-07-10',
            'payment_method' => Payment::METHOD_CASH, 'status' => Payment::STATUS_FAILED]);
        $this->actingAs($this->admin)->delete("/admin/invoices/{$withPayment->id}")
            ->assertRedirect(route('admin.invoices.index'))->assertSessionHas('error');
        $this->assertDatabaseHas('invoices', ['id' => $withPayment->id]);
        $this->assertDatabaseHas('payments', ['invoice_id' => $withPayment->id]);

        [$emptyContract, , , $reading] = $this->fixture('DELETE-EMPTY', 8);
        $empty = $this->issue($emptyContract, 8);
        $this->delete("/admin/invoices/{$empty->id}")->assertSessionHas('success');
        $this->assertDatabaseMissing('invoices', ['id' => $empty->id]);
        $this->assertDatabaseMissing('invoice_details', ['invoice_id' => $empty->id]);
        $this->assertDatabaseHas('utility_readings', ['id' => $reading->id]);
        $this->delete("/admin/invoices/{$empty->id}")->assertNotFound();
    }

    private function fixture(string $key, int $month = 7): array
    {
        $client = $this->user($this->clientRole, strtolower($key).'@example.test');
        $tenant = Tenant::create(['user_id' => $client->id, 'full_name' => 'Tenant '.$key,
            'cccd' => str_pad((string) abs(crc32($key)), 12, '0'), 'phone' => '09'.str_pad((string) abs(crc32('p'.$key)), 8, '0')]);
        $room = Room::create(['room_code' => 'ROOM-'.$key, 'floor' => 1, 'price' => 3000000,
            'area' => 25, 'max_people' => 4, 'current_people' => 1, 'status' => Room::STATUS_OCCUPIED]);
        $contract = Contract::create(['contract_code' => 'CONTRACT-'.$key, 'room_id' => $room->id,
            'tenant_id' => $tenant->id, 'monthly_rent' => 3000000, 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'status' => Contract::STATUS_ACTIVE,
            'internet_enabled' => true, 'service_enabled' => true, 'parking_quantity' => 1]);
        $reading = $this->reading($room, $month);
        $reading->update(['contract_id' => $contract->id, 'reading_type' => 'periodic']);

        return [$contract, $room, $tenant, $reading];
    }

    private function reading(Room $room, int $month, string $status = 'confirmed'): UtilityReading
    {
        return UtilityReading::create(['room_id' => $room->id, 'month' => $month, 'year' => 2026,
            'record_date' => "2026-{$month}-28", 'electricity_old' => 100, 'electricity_new' => 120,
            'water_old' => 50, 'water_new' => 55, 'status' => $status]);
    }

    private function issue(Contract $contract, int $month = 7): Invoice
    {
        return app(InvoiceGenerator::class)->issue($contract, $month, 2026);
    }

    private function user(Role $role, string $email): User
    {
        return User::create(['name' => 'User', 'email' => $email, 'role_id' => $role->id,
            'password' => 'password', 'status' => User::STATUS_ACTIVE]);
    }
}
