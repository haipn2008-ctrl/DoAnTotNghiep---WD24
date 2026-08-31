<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\FeeSchedule;
use App\Models\Invoice;
use App\Models\InvoicePaymentDelayRequest;
use App\Models\InvoiceReminder;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use App\Services\InvoiceGenerator;
use App\Services\OverdueInvoiceService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'motorcycle_parking_fee' => 80000, 'car_parking_fee' => 500000,
            'invoice_day' => 5, 'payment_due_days' => 5]);
    }

    public function test_only_admin_can_access_invoice_pages_preview_issue_and_direct_delete(): void
    {
        [$contract, , , $reading] = $this->fixture('AUTH');
        $invoice = $this->issue($contract);
        $this->get('/admin/invoices')->assertRedirect('/login');
        $this->post(route('admin.invoices.cancel', $invoice), ['cancellation_reason' => 'Không có quyền thao tác.'])->assertRedirect('/login');
        $client = $this->user($this->clientRole, 'invoice-client@example.test');
        $this->actingAs($client)->get('/admin/invoices')->assertForbidden();
        $this->get("/admin/invoices/contracts/{$contract->id}/preview?month=7&year=2026")->assertForbidden();
        $this->post("/admin/invoices/contracts/{$contract->id}/issue", ['month' => 7, 'year' => 2026])->assertForbidden();
        $this->post(route('admin.invoices.adjustments.store', $invoice), [
            'direction' => 'debit', 'amount' => 1, 'reason' => 'Không có quyền thao tác.',
        ])->assertForbidden();
        $this->delete("/admin/invoices/{$invoice->id}")->assertMethodNotAllowed();
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
            ->assertOk()->assertJsonPath('invoice_date', '2026-07-05')
            ->assertJsonPath('due_date', '2026-07-10')
            ->assertJsonPath('total_amount', 3320000);
        $this->assertSame(['room', 'electricity', 'water', 'internet', 'service'],
            collect($response->json('lines'))->pluck('type')->all());
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_details', 0);
    }

    public function test_invoice_can_be_issued_before_scheduled_date_and_records_actual_issuer(): void
    {
        Carbon::setTestNow('2026-08-31 12:00:00');

        try {
            [$contract, , , $reading] = $this->fixture('SCHEDULED-ISSUE', 9);

            $this->actingAs($this->admin)
                ->get('/admin/invoices/generate?month=9&year=2026')
                ->assertOk()
                ->assertSee('05/09/2026')
                ->assertSee('Xác nhận và phát hành')
                ->assertDontSee('Chưa đến ngày phát hành');

            $this->getJson("/admin/invoices/contracts/{$contract->id}/preview?month=9&year=2026")
                ->assertOk()
                ->assertJsonPath('invoice_date', '2026-09-05');

            $this->postJson("/admin/invoices/contracts/{$contract->id}/issue", [
                'month' => 9,
                'year' => 2026,
            ])->assertOk();

            $this->assertDatabaseHas('invoices', [
                'contract_id' => $contract->id,
                'month' => 9,
                'year' => 2026,
                'invoice_date' => '2026-09-05 00:00:00',
                'issued_at' => '2026-08-31 12:00:00',
                'issued_by' => $this->admin->id,
            ]);
            $this->assertTrue($reading->fresh()->isLocked());

            $invoice = Invoice::query()->where('contract_id', $contract->id)->firstOrFail();
            $this->get(route('admin.invoices.show', $invoice))
                ->assertOk()
                ->assertSee('Phát hành thực tế')
                ->assertSee('12:00 31/08/2026')
                ->assertSee($this->admin->name);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_june_fifth_collects_may_rent_and_may_utilities(): void
    {
        [$contract] = $this->fixture('MAY-JUNE', 6);

        $preview = app(InvoiceGenerator::class)->preview($contract, 6, 2026);

        $this->assertSame('2026-06-05', $preview['invoice_date']);
        $this->assertSame('2026-06-10', $preview['due_date']);
        $this->assertSame(5, $preview['utility_month']);
        $this->assertSame(2026, $preview['utility_year']);
        $this->assertSame([
            'Tiền phòng tháng 5/2026',
            'Tiền điện tháng 5/2026',
            'Tiền nước tháng 5/2026',
            'Phí internet tháng 5/2026',
            'Phí dịch vụ tháng 5/2026',
        ], collect($preview['lines'])->pluck('name')->all());
    }

    public function test_first_month_rent_is_deferred_prorated_and_due_on_next_month_fifth(): void
    {
        [$contract] = $this->fixture('DEFERRED-FIRST-MONTH', 6);
        $contract->forceFill(['start_date' => '2026-05-25'])->save();

        $preview = app(InvoiceGenerator::class)->preview($contract->fresh(), 6, 2026);

        $this->assertSame('2026-06-05', $preview['invoice_date']);
        $this->assertSame('2026-06-10', $preview['due_date']);
        $this->assertSame(677419.0, $preview['room_fee']);
        $this->assertSame(997419.0, $preview['total_amount']);
        $this->assertSame('Tiền phòng tháng 5/2026', $preview['lines'][0]['name']);
        $this->assertSame(7, $preview['lines'][0]['quantity']);
        $this->assertSame('ngày', $preview['lines'][0]['unit']);
    }

    public function test_legacy_first_month_payment_is_credited_instead_of_charged_twice(): void
    {
        [$contract, $room] = $this->fixture('FIRST-MONTH-CREDIT', 6);
        $contract->forceFill(['start_date' => '2026-05-01'])->save();
        $legacyInvoice = Invoice::create([
            'contract_id' => $contract->id,
            'invoice_type' => Invoice::TYPE_FIRST_MONTH_RENT,
            'room_id' => $room->id,
            'invoice_code' => 'FMR-LEGACY-CREDIT',
            'month' => 5,
            'year' => 2026,
            'invoice_date' => '2026-05-01',
            'due_date' => '2026-05-05',
            'room_fee' => 3000000,
            'total_amount' => 3000000,
            'status' => Invoice::STATUS_PARTIAL,
        ]);
        Payment::create([
            'invoice_id' => $legacyInvoice->id,
            'amount_paid' => 1000000,
            'payment_date' => '2026-05-02',
            'payment_method' => Payment::METHOD_CASH,
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $preview = app(InvoiceGenerator::class)->preview($contract->fresh(), 6, 2026);

        $this->assertSame(2000000.0, $preview['room_fee']);
        $this->assertSame(2320000.0, $preview['total_amount']);
        $this->assertSame(-1000000.0, collect($preview['lines'])->firstWhere('type', 'first_month_credit')['amount']);
    }

    public function test_configured_invoice_schedule_handles_short_months_and_year_rollover(): void
    {
        Setting::current()->update(['invoice_day' => 31, 'payment_due_days' => 10]);
        [$contract, , , $reading] = $this->fixture('CONFIGURED-DATES');

        $reading->update([
            'month' => 11,
            'year' => 2026,
            'record_date' => '2026-11-30',
        ]);

        $december = app(InvoiceGenerator::class)->preview($contract, 12, 2026);
        $this->assertSame('2026-12-31', $december['invoice_date']);
        $this->assertSame('2027-01-10', $december['due_date']);

        $reading->update([
            'month' => 1,
            'year' => 2026,
            'record_date' => '2026-01-31',
        ]);

        $february = app(InvoiceGenerator::class)->preview($contract, 2, 2026);
        $this->assertSame('2026-02-28', $february['invoice_date']);
        $this->assertSame('2026-03-10', $february['due_date']);
    }

    public function test_invoice_uses_fee_schedule_of_service_month_and_locks_it_after_issue(): void
    {
        $oldRates = FeeSchedule::create([
            'effective_from' => '2026-01-01',
            'electric_price' => 3000,
            'water_price' => 15000,
            'internet_fee' => 80000,
            'service_fee' => 40000,
        ]);
        FeeSchedule::create([
            'effective_from' => '2026-07-01',
            'electric_price' => 5000,
            'water_price' => 25000,
            'internet_fee' => 120000,
            'service_fee' => 60000,
        ]);
        [$contract, $room] = $this->fixture('HISTORICAL-RATES');

        $julyInvoicePreview = app(InvoiceGenerator::class)->preview($contract, 7, 2026);
        $this->assertSame(3000.0, collect($julyInvoicePreview['lines'])->firstWhere('type', 'electricity')['unit_price']);

        UtilityReading::create([
            'room_id' => $room->id,
            'contract_id' => $contract->id,
            'month' => 7,
            'year' => 2026,
            'record_date' => '2026-07-31',
            'reading_type' => 'periodic',
            'electricity_old' => 120,
            'electricity_new' => 140,
            'water_old' => 55,
            'water_new' => 60,
            'status' => 'confirmed',
        ]);
        $augustInvoicePreview = app(InvoiceGenerator::class)->preview($contract, 8, 2026);
        $this->assertSame(5000.0, collect($augustInvoicePreview['lines'])->firstWhere('type', 'electricity')['unit_price']);

        $invoice = $this->issue($contract);
        $this->assertSame($oldRates->id, $invoice->fee_schedule_id);

        $this->actingAs($this->admin)->put('/admin/settings/fees', [
            'electric_price' => 9999,
            'water_price' => 9999,
            'internet_fee' => 9999,
            'service_fee' => 9999,
            'invoice_day' => 5,
            'payment_due_days' => 10,
            'fee_effective_from' => '2026-01',
        ])->assertSessionHasErrors('fee_effective_from');

        $this->assertSame('3000.00', $oldRates->fresh()->electric_price);
        $this->assertSame('60000.00', $invoice->fresh()->electricity_fee);
    }

    public function test_historical_parking_registration_never_creates_a_charge(): void
    {
        [$contract] = $this->fixture('CAR-PARKING');
        $contract->update([
            'parking_vehicle_type' => Contract::PARKING_CAR,
            'parking_quantity' => 2,
        ]);

        $preview = app(InvoiceGenerator::class)->preview($contract->fresh(), 7, 2026);
        $this->assertNull(collect($preview['lines'])->firstWhere('type', 'parking'));
        $this->assertSame(3320000.0, $preview['total_amount']);
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
        $this->assertSame('3320000.00', $invoice->total_amount);
        $this->assertDatabaseCount('invoice_details', 5);

        Setting::first()->update(['electric_price' => 999999]);
        $reading->update(['electricity_new' => 120]);
        $this->assertSame('3320000.00', $invoice->fresh()->total_amount);
        $this->assertSame('70000.00', $invoice->fresh()->electricity_fee);

        $this->postJson("/admin/invoices/contracts/{$contract->id}/issue", ['month' => 7, 'year' => 2026])
            ->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_details', 5);
    }

    public function test_missing_or_unconfirmed_reading_and_contract_outside_period_are_rejected(): void
    {
        [$contract, , , $reading] = $this->fixture('INVALID');
        DB::table('utility_readings')->where('id', $reading->id)->delete();
        $this->actingAs($this->admin)->getJson("/admin/invoices/contracts/{$contract->id}/preview?month=7&year=2026")
            ->assertStatus(422)->assertJsonFragment(['message' => 'Phòng ROOM-INVALID chưa chốt điện nước tháng 6/2026.']);
        $reading = $this->reading($contract->room, 6, 'draft');
        $this->postJson("/admin/invoices/contracts/{$contract->id}/issue", ['month' => 7, 'year' => 2026])->assertStatus(422);
        $reading->update(['status' => 'confirmed']);
        $contract->update(['start_date' => '2026-08-01']);
        $this->post('/admin/invoices/generate', ['contract_id' => $contract->id, 'month' => 7, 'year' => 2026])
            ->assertSessionHasErrors('invoice');
        $contract->forceFill([
            'start_date' => '2026-01-01',
            'actual_end_date' => '2026-06-30',
            'status' => Contract::STATUS_TERMINATED,
        ])->save();
        $this->postJson("/admin/invoices/contracts/{$contract->id}/issue", ['month' => 7, 'year' => 2026])->assertOk();
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_terminated_and_active_contracts_in_same_room_and_month_can_receive_separate_invoices(): void
    {
        [$newContract, $room, $tenant] = $this->fixture('SAME-ROOM-NEW', 8);
        $oldContract = Contract::query()->forceCreate([
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
            'month' => 7, 'year' => 2026, 'record_date' => '2026-07-31',
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
        $this->assertDatabaseCount('invoice_details', 10);
    }

    public function test_issuing_final_invoice_moves_inactive_former_tenant_to_settling(): void
    {
        [$contract, , $tenant] = $this->fixture('FINAL-INVOICE');
        $contract->forceFill([
            'actual_end_date' => '2026-07-10',
            'status' => Contract::STATUS_TERMINATED,
        ])->save();
        $tenant->user->update(['status' => User::STATUS_INACTIVE]);

        $invoice = $this->issue($contract);

        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status);
        $this->assertSame(User::STATUS_SETTLING, $tenant->user->fresh()->status);
    }

    public function test_previous_tenant_invoice_does_not_disable_new_contract_in_same_room_and_month(): void
    {
        [$newContract, $room, $tenant] = $this->fixture('TURNOVER-INVOICE');
        $oldContract = Contract::query()->forceCreate([
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
            ->assertViewHas('issuedContractIds', fn ($ids) => in_array($oldContract->id, $ids) && ! in_array($newContract->id, $ids)
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

    public function test_issued_invoice_is_never_deleted_and_only_empty_invoice_can_be_cancelled_then_reissued(): void
    {
        [$paidContract] = $this->fixture('DELETE-PAID');
        $withPayment = $this->issue($paidContract);
        Payment::create(['invoice_id' => $withPayment->id, 'amount_paid' => 1, 'payment_date' => '2026-07-10',
            'payment_method' => Payment::METHOD_CASH, 'status' => Payment::STATUS_FAILED]);
        $this->actingAs($this->admin)->post(route('admin.invoices.cancel', $withPayment), [
            'cancellation_reason' => 'Sai thông tin nhưng hóa đơn đã có giao dịch.',
        ])->assertSessionHasErrors('cancellation_reason');
        $this->assertDatabaseHas('invoices', ['id' => $withPayment->id]);
        $this->assertDatabaseHas('payments', ['invoice_id' => $withPayment->id]);

        [$emptyContract, , , $reading] = $this->fixture('DELETE-EMPTY', 8);
        $empty = $this->issue($emptyContract, 8);
        $this->delete("/admin/invoices/{$empty->id}")->assertMethodNotAllowed();
        $this->assertDatabaseHas('invoices', ['id' => $empty->id, 'status' => Invoice::STATUS_UNPAID]);

        $this->post(route('admin.invoices.cancel', $empty), [
            'cancellation_reason' => 'Hóa đơn lập nhầm cần phát hành lại.',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('invoices', [
            'id' => $empty->id,
            'status' => Invoice::STATUS_CANCELLED,
            'cancelled_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('invoice_details', ['invoice_id' => $empty->id]);
        $this->assertTrue($reading->fresh()->isConfirmed());

        $replacement = $this->issue($emptyContract, 8);
        $this->assertSame(2, $replacement->revision);
        $this->assertNotSame($empty->id, $replacement->id);
        $this->assertTrue($reading->fresh()->isLocked());
    }

    public function test_legacy_direct_adjustment_is_rejected_to_preserve_the_original_invoice(): void
    {
        [$contract] = $this->fixture('ADJUSTMENT');
        $invoice = $this->issue($contract);
        $originalTotal = $invoice->total_amount;

        $this->actingAs($this->admin)->post(route('admin.invoices.adjustments.store', $invoice), [
            'direction' => 'debit',
            'amount' => 100000,
            'reason' => 'Bổ sung khoản phí dịch vụ còn thiếu.',
        ])->assertSessionHasErrors('direction');

        $invoice->refresh();
        $this->assertSame($originalTotal, $invoice->total_amount);
        $this->assertSame('0.00', $invoice->adjustment_amount);
        $this->assertDatabaseCount('invoice_adjustments', 0);
    }

    public function test_admin_creates_an_independent_supplemental_invoice_from_a_rental_invoice(): void
    {
        [$contract, , $tenant] = $this->fixture('SUPPLEMENTAL');
        $source = $this->issue($contract);
        $sourceTotal = $source->total_amount;

        $this->actingAs($this->admin)->post(route('admin.invoices.supplemental.store', $source), [
            'category' => 'damage',
            'amount' => 250000,
            'description' => 'Bồi thường khóa cửa bị hỏng sau khi đối chiếu.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $supplemental = Invoice::query()
            ->where('invoice_type', Invoice::TYPE_SUPPLEMENTAL)
            ->sole();
        $this->assertSame($source->id, $supplemental->parent_invoice_id);
        $this->assertSame($source->contract_id, $supplemental->contract_id);
        $this->assertSame('250000.00', $supplemental->total_amount);
        $this->assertSame($sourceTotal, $source->fresh()->total_amount);
        $this->assertSame(Invoice::STATUS_UNPAID, $source->fresh()->status);
        $this->assertStringStartsWith('SUP-', $supplemental->invoice_code);
        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $supplemental->id,
            'type' => 'supplemental_damage',
            'amount' => 250000,
        ]);

        $this->get(route('admin.invoices.show', $source))
            ->assertOk()->assertSee($supplemental->invoice_code)->assertSee('Hóa đơn bổ sung');
        $this->actingAs($tenant->user)->get(route('client.invoices.show', $supplemental))
            ->assertOk()->assertSee('Hóa đơn bổ sung')->assertSee($source->invoice_code);
    }

    public function test_credit_is_applied_to_the_next_month_invoice_and_restored_if_that_invoice_is_cancelled(): void
    {
        [$contract, $room] = $this->fixture('NEXT-CREDIT');
        $source = $this->issue($contract);

        $this->actingAs($this->admin)->post(route('admin.invoices.next-invoice-credits.store', $source), [
            'amount' => 420000,
            'reason' => 'Giảm tiền sau khi đối chiếu lại chi phí dịch vụ tháng trước.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $credit = DB::table('contract_credits')->sole();
        $this->assertEquals(420000, $credit->remaining_amount);
        $this->assertSame('0.00', $source->fresh()->adjustment_amount);
        $this->assertSame(3320000.0, $source->fresh()->payable_amount);

        $reading = $this->reading($room, 7);
        $reading->update(['contract_id' => $contract->id, 'reading_type' => 'periodic']);
        $preview = app(InvoiceGenerator::class)->preview($contract, 8, 2026);
        $this->assertSame(420000.0, $preview['credit_amount']);
        $this->assertSame(2900000.0, $preview['total_amount']);
        $this->assertSame('contract_credit', collect($preview['lines'])->last()['type']);

        $nextInvoice = $this->issue($contract, 8);
        $this->assertSame(2900000.0, $nextInvoice->payable_amount);
        $this->assertDatabaseHas('contract_credits', [
            'id' => $credit->id,
            'remaining_amount' => 0,
        ]);
        $this->assertDatabaseHas('contract_credit_applications', [
            'contract_credit_id' => $credit->id,
            'invoice_id' => $nextInvoice->id,
            'amount' => 420000,
        ]);

        $this->post(route('admin.invoices.cancel', $nextInvoice), [
            'cancellation_reason' => 'Hủy hóa đơn để kiểm tra hoàn lại khoản giảm.',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contract_credits', [
            'id' => $credit->id,
            'remaining_amount' => 420000,
        ]);
        $this->assertDatabaseMissing('contract_credit_applications', ['invoice_id' => $nextInvoice->id]);
    }

    public function test_debt_aging_starts_after_due_date_and_uses_confirmed_buckets(): void
    {
        Carbon::setTestNow('2026-08-25 15:00:00');

        try {
            [$contract] = $this->fixture('DEBT-AGING');
            $invoice = $this->issue($contract);
            $cases = [
                '2026-08-26' => ['upcoming', 0, false],
                '2026-08-25' => ['due_today', 0, false],
                '2026-08-24' => ['overdue_1_3', 1, true],
                '2026-08-21' => ['overdue_4_7', 4, true],
                '2026-08-17' => ['overdue_8_14', 8, true],
                '2026-08-10' => ['overdue_15_plus', 15, true],
            ];

            foreach ($cases as $dueDate => [$bucket, $days, $isOverdue]) {
                $invoice->update(['due_date' => $dueDate]);
                $invoice->refresh();

                $this->assertSame($bucket, $invoice->debt_bucket);
                $this->assertSame($days, $invoice->days_overdue);
                $this->assertSame($isOverdue, $invoice->isOverdue());
            }

            $invoice->update(['status' => Invoice::STATUS_PAID]);
            $this->assertSame('settled', $invoice->fresh()->debt_bucket);
            $this->assertSame(0, $invoice->fresh()->days_overdue);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_invoice_reminder_is_unique_per_day_and_keeps_actor_snapshot(): void
    {
        [$contract] = $this->fixture('REMINDER-UNIQUE');
        $invoice = $this->issue($contract);
        $reminder = InvoiceReminder::create([
            'invoice_id' => $invoice->id,
            'channel' => InvoiceReminder::CHANNEL_SYSTEM,
            'note' => 'Đã gửi thông báo đến khách.',
            'reminded_by' => $this->admin->id,
            'reminded_by_name' => $this->admin->name,
            'reminder_date' => '2026-08-25',
            'reminded_at' => '2026-08-25 09:00:00',
        ]);

        $this->assertSame('Thông báo trong hệ thống', $reminder->channel_label);
        $this->assertSame($this->admin->name, $reminder->reminded_by_name);

        $this->expectException(QueryException::class);
        InvoiceReminder::create([
            'invoice_id' => $invoice->id,
            'channel' => InvoiceReminder::CHANNEL_SYSTEM,
            'reminded_by' => $this->admin->id,
            'reminded_by_name' => $this->admin->name,
            'reminder_date' => '2026-08-25',
            'reminded_at' => '2026-08-25 15:00:00',
        ]);
    }

    public function test_invoice_reminder_history_cannot_be_updated_or_deleted(): void
    {
        [$contract] = $this->fixture('REMINDER-IMMUTABLE');
        $invoice = $this->issue($contract);
        $reminder = InvoiceReminder::create([
            'invoice_id' => $invoice->id,
            'channel' => InvoiceReminder::CHANNEL_SYSTEM,
            'reminded_by' => $this->admin->id,
            'reminded_by_name' => $this->admin->name,
            'reminder_date' => '2026-08-25',
            'reminded_at' => '2026-08-25 10:00:00',
        ]);

        foreach (['update', 'delete'] as $action) {
            try {
                $action === 'update'
                    ? $reminder->update(['note' => 'Không được sửa'])
                    : $reminder->delete();
                $this->fail('Reminder history must be immutable.');
            } catch (\LogicException $exception) {
                $this->assertStringContainsString('không thể', $exception->getMessage());
            }
        }

        $this->assertDatabaseHas('invoice_reminders', [
            'id' => $reminder->id,
            'note' => null,
        ]);
    }

    public function test_debt_list_filters_aging_and_calculates_approved_pending_and_remaining_amounts(): void
    {
        Carbon::setTestNow('2026-08-25 10:00:00');

        try {
            [$overdueContract] = $this->fixture('DEBT-LIST-OVERDUE');
            $overdue = $this->issue($overdueContract);
            $overdue->update(['due_date' => today()->subDays(4)]);
            Payment::create([
                'invoice_id' => $overdue->id,
                'amount_paid' => 1000000,
                'payment_date' => today(),
                'payment_method' => Payment::METHOD_CASH,
                'status' => Payment::STATUS_SUCCESS,
            ]);
            Payment::create([
                'invoice_id' => $overdue->id,
                'amount_paid' => 500000,
                'payment_date' => today(),
                'payment_method' => Payment::METHOD_QR,
                'status' => Payment::STATUS_PENDING,
            ]);
            $overdue->refreshStatus();

            [$upcomingContract] = $this->fixture('DEBT-LIST-UPCOMING');
            $upcoming = $this->issue($upcomingContract);
            $upcoming->update(['due_date' => today()->addDay()]);

            [$paidContract] = $this->fixture('DEBT-LIST-PAID');
            $paid = $this->issue($paidContract);
            $paid->update(['status' => Invoice::STATUS_PAID, 'due_date' => today()->subDays(20)]);

            $response = $this->actingAs($this->admin)->get(route('admin.debts.index', [
                'bucket' => 'overdue_4_7',
            ]));

            $response->assertSuccessful()
                ->assertSee($overdue->invoice_code)
                ->assertDontSee($upcoming->invoice_code)
                ->assertDontSee($paid->invoice_code)
                ->assertViewHas('invoices', fn ($invoices) => $invoices->total() === 1
                    && $invoices->first()->id === $overdue->id)
                ->assertViewHas('summary', function ($summary) use ($overdue): bool {
                    return (int) $summary->invoice_count === 1
                        && (float) $summary->pending_amount === 500000.0
                        && (float) $summary->remaining_amount === $overdue->payable_amount - 1000000.0;
                });

            $this->get(route('admin.debts.index', ['keyword' => $upcoming->room->room_code]))
                ->assertSuccessful()
                ->assertSee($upcoming->invoice_code)
                ->assertDontSee($overdue->invoice_code);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_debt_list_requires_admin_and_rejects_invalid_filters(): void
    {
        [$contract] = $this->fixture('DEBT-AUTH');
        $client = $contract->tenant->user;

        $this->get(route('admin.debts.index'))->assertRedirect('/login');
        $this->actingAs($client)->get(route('admin.debts.index'))->assertForbidden();
        $this->actingAs($this->admin)
            ->get(route('admin.debts.index', ['bucket' => 'invalid', 'keyword' => str_repeat('x', 101)]))
            ->assertSessionHasErrors(['bucket', 'keyword']);
    }

    public function test_admin_records_one_manual_reminder_per_invoice_per_day_and_sees_full_history(): void
    {
        Carbon::setTestNow('2026-08-25 14:30:00');

        try {
            [$contract] = $this->fixture('REMINDER-STORE');
            $invoice = $this->issue($contract);
            $invoice->update(['due_date' => today()->subDays(4)]);
            InvoiceReminder::create([
                'invoice_id' => $invoice->id,
                'channel' => InvoiceReminder::CHANNEL_SYSTEM,
                'note' => 'Thông báo trong hệ thống ngày hôm trước.',
                'reminded_by' => $this->admin->id,
                'reminded_by_name' => $this->admin->name,
                'reminder_date' => today()->subDay(),
                'reminded_at' => now()->subDay(),
            ]);

            $client = $contract->tenant->user;
            $this->post(route('admin.debts.reminders.store', $invoice))->assertRedirect('/login');
            $this->actingAs($client)
                ->post(route('admin.debts.reminders.store', $invoice))
                ->assertForbidden();

            $this->actingAs($this->admin)
                ->post(route('admin.debts.reminders.store', $invoice), [
                    'note' => 'Vui lòng thanh toán trước cuối ngày.',
                    'reminder_date' => '2000-01-01',
                    'reminded_by' => $client->id,
                ])
                ->assertRedirect(route('admin.debts.show', $invoice))
                ->assertSessionHas('success');

            $storedReminder = InvoiceReminder::query()
                ->where('invoice_id', $invoice->id)
                ->whereDate('reminder_date', today())
                ->sole();
            $this->assertSame(InvoiceReminder::CHANNEL_SYSTEM, $storedReminder->channel);
            $this->assertSame($this->admin->id, $storedReminder->reminded_by);
            $this->assertSame($this->admin->name, $storedReminder->reminded_by_name);
            $this->assertSame(today()->toDateString(), $storedReminder->reminder_date->toDateString());
            $this->assertDatabaseHas('notifications', [
                'notifiable_type' => User::class,
                'notifiable_id' => $client->id,
            ]);

            $this->post(route('admin.debts.reminders.store', $invoice))
                ->assertSessionHasErrors('reminder');
            $this->assertSame(2, $invoice->reminders()->count());

            $this->get(route('admin.debts.show', $invoice))
                ->assertSuccessful()
                ->assertSee('Thông báo trong hệ thống ngày hôm trước.')
                ->assertSee('Vui lòng thanh toán trước cuối ngày.')
                ->assertSee('đã được ghi nhận nhắc hôm nay');

            $notification = $client->notifications()->sole();
            $this->actingAs($client)->get(route('client.notifications.index'))
                ->assertSuccessful()
                ->assertSee($invoice->invoice_code)
                ->assertSee('Vui lòng thanh toán trước cuối ngày.')
                ->assertSee('Mới');
            $this->get(route('client.notifications.open', $notification->id))
                ->assertRedirect(route('client.invoices.show', $invoice));
            $this->assertNotNull($notification->fresh()->read_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_system_reminder_validates_message_and_rejects_settled_invoice(): void
    {
        [$contract] = $this->fixture('REMINDER-VALIDATION');
        $invoice = $this->issue($contract);

        $this->actingAs($this->admin)
            ->post(route('admin.debts.reminders.store', $invoice), [
                'note' => str_repeat('x', 1001),
            ])
            ->assertSessionHasErrors('note');

        $invoice->update(['status' => Invoice::STATUS_PAID]);
        $this->post(route('admin.debts.reminders.store', $invoice))
            ->assertSessionHasErrors('reminder');
        $this->assertDatabaseCount('invoice_reminders', 0);
        $this->get(route('admin.debts.show', $invoice))
            ->assertSuccessful()
            ->assertSee('không còn công nợ cần nhắc');
    }

    public function test_due_today_job_notifies_the_client_once_on_the_effective_deadline(): void
    {
        Carbon::setTestNow('2026-08-10 08:00:00');

        try {
            [$contract] = $this->fixture('DUE-TODAY');
            $invoice = $this->issue($contract);
            $invoice->update(['due_date' => '2026-08-10']);
            $client = $contract->tenant->user;

            $this->assertSame(1, app(OverdueInvoiceService::class)->notifyDueToday());
            $this->assertSame(0, app(OverdueInvoiceService::class)->notifyDueToday());
            $this->assertNotNull($invoice->fresh()->due_notified_at);
            $notification = $client->notifications()->sole();
            $this->assertSame('invoice_due_today', $notification->data['type']);
            $this->assertSame($invoice->id, $notification->data['invoice_id']);

            $this->actingAs($client)->get(route('client.notifications.open', $notification->id))
                ->assertRedirect(route('client.invoices.show', $invoice));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_overdue_job_notifies_once_and_admin_can_approve_a_payment_extension(): void
    {
        Carbon::setTestNow('2026-08-11 00:10:00');

        try {
            [$contract] = $this->fixture('DELAY-APPROVE');
            $invoice = $this->issue($contract);
            $invoice->update(['due_date' => '2026-08-10']);
            $client = $contract->tenant->user;

            $this->assertSame(1, app(OverdueInvoiceService::class)->notifyNewlyOverdue());
            $this->assertSame(0, app(OverdueInvoiceService::class)->notifyNewlyOverdue());
            $this->assertNotNull($invoice->fresh()->overdue_notified_at);
            $this->assertSame(1, $invoice->reminders()->count());
            $this->assertSame('invoice_overdue', $client->notifications()->sole()->data['type']);

            $this->actingAs($client)->post(route('client.invoices.payment-delay-request.store', $invoice), [
                'reason' => 'Tôi nhận lương chậm và xin thêm thời gian thanh toán.',
                'promised_payment_date' => '2026-08-15',
            ])->assertSessionHas('success');

            $delayRequest = InvoicePaymentDelayRequest::query()->sole();
            $this->assertSame(InvoicePaymentDelayRequest::STATUS_PENDING, $delayRequest->status);
            $this->assertDatabaseHas('contract_lifecycle_alerts', [
                'type' => 'payment_delay_request',
            ]);
            $this->assertSame(
                $delayRequest->id,
                (int) ContractLifecycleAlert::query()->sole()->metadata['reference_id']
            );

            $this->actingAs($this->admin)->post(route('admin.payment-delay-requests.approve', $delayRequest), [
                'approved_until' => '2026-08-16',
                'review_note' => 'Đồng ý gia hạn một lần cho khách thuê.',
            ])->assertSessionHas('success');

            $this->assertDatabaseHas('invoice_payment_delay_requests', [
                'id' => $delayRequest->id,
                'status' => InvoicePaymentDelayRequest::STATUS_APPROVED,
                'reviewed_by' => $this->admin->id,
            ]);
            $invoice->refresh();
            $this->assertSame('2026-08-16', $invoice->effective_due_date->toDateString());
            $this->assertFalse($invoice->isOverdue());
            $this->assertNull($invoice->overdue_notified_at);
            $this->assertNotNull($client->notifications()->where('data->type', 'payment_delay_decision')->first());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_rejected_delay_request_creates_final_warning_without_ending_contract(): void
    {
        Carbon::setTestNow('2026-08-11 09:00:00');

        try {
            [$contract] = $this->fixture('DELAY-REJECT');
            $invoice = $this->issue($contract);
            $invoice->update(['due_date' => '2026-08-10']);
            $client = $contract->tenant->user;

            $this->actingAs($client)->post(route('client.invoices.payment-delay-request.store', $invoice), [
                'reason' => 'Tôi đang gặp sự cố chuyển khoản và cần thêm thời gian.',
                'promised_payment_date' => '2026-08-15',
            ])->assertSessionHas('success');
            $delayRequest = InvoicePaymentDelayRequest::query()->sole();

            $this->actingAs($this->admin)->post(route('admin.payment-delay-requests.reject', $delayRequest), [
                'review_note' => 'Không chấp nhận vì khách đã trễ nhiều lần trước đó.',
            ])->assertSessionHas('success');

            $this->assertDatabaseHas('invoice_payment_delay_requests', [
                'id' => $delayRequest->id,
                'status' => InvoicePaymentDelayRequest::STATUS_REJECTED,
                'reviewed_by' => $this->admin->id,
            ]);
            $this->assertSame(Contract::STATUS_ACTIVE, $contract->fresh()->status);
            $this->assertSame(Room::STATUS_OCCUPIED, $contract->room->fresh()->status);
            $this->assertNotNull($client->notifications()->where('data->type', 'payment_delay_decision')->first());
            $this->actingAs($client)->get(route('client.invoices.show', $invoice))
                ->assertSuccessful()
                ->assertSee('Ban quản lý đã từ chối lý do chậm thanh toán');
            $this->actingAs($this->admin)->get(route('admin.debts.show', $invoice))
                ->assertSuccessful()
                ->assertSee(route('admin.contracts.check-out.form', $contract));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function fixture(string $key, int $month = 7): array
    {
        $client = $this->user($this->clientRole, strtolower($key).'@example.test');
        $tenant = Tenant::create(['user_id' => $client->id, 'full_name' => 'Tenant '.$key,
            'cccd' => str_pad((string) abs(crc32($key)), 12, '0'), 'phone' => '09'.str_pad((string) abs(crc32('p'.$key)), 8, '0')]);
        $room = Room::create(['room_code' => 'ROOM-'.$key, 'floor' => 1, 'price' => 3000000,
            'area' => 25, 'max_people' => 4, 'current_people' => 1, 'status' => Room::STATUS_OCCUPIED]);
        $contract = Contract::query()->forceCreate(['contract_code' => 'CONTRACT-'.$key, 'room_id' => $room->id,
            'tenant_id' => $tenant->id, 'monthly_rent' => 3000000, 'start_date' => '2026-01-01',
            'end_date' => '2026-12-31', 'status' => Contract::STATUS_ACTIVE,
            'internet_enabled' => false, 'service_enabled' => false, 'parking_quantity' => 1]);
        $reading = $this->reading($room, $month - 1);
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
