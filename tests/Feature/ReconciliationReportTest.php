<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReconciliationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_separates_invoice_cohort_cash_timing_pending_write_off_and_overpayment(): void
    {
        [$contract, $room] = $this->context();
        $regular = $this->invoice($contract, $room, 'REGULAR', '2026-08-05', 1000, Invoice::STATUS_PARTIAL);
        $this->payment($regular, 600, '2026-09-01', Payment::STATUS_SUCCESS);
        $this->payment($regular, 200, '2026-08-20', Payment::STATUS_PENDING);

        $writtenOff = $this->invoice($contract, $room, 'WRITTEN-OFF', '2026-08-05', 500, Invoice::STATUS_WRITTEN_OFF);
        $this->payment($writtenOff, 100, '2026-08-10', Payment::STATUS_SUCCESS);

        $overpaid = $this->invoice($contract, $room, 'OVERPAID', '2026-08-05', 300, Invoice::STATUS_PAID);
        $this->payment($overpaid, 350, '2026-08-11', Payment::STATUS_SUCCESS);

        $this->invoice($contract, $room, 'CANCELLED', '2026-08-05', 1000, Invoice::STATUS_CANCELLED);
        $prior = $this->invoice($contract, $room, 'PRIOR', '2026-07-05', 400, Invoice::STATUS_PAID);
        $this->payment($prior, 400, '2026-08-12', Payment::STATUS_SUCCESS);

        $report = app(ReconciliationReport::class);
        $summary = $report->summary(8, 2026);

        $this->assertSame(3, $summary['invoice_count']);
        $this->assertSame(1800.0, $summary['gross_billed']);
        $this->assertSame(1050.0, $summary['cohort_collected']);
        $this->assertSame(850.0, $summary['cash_received']);
        $this->assertSame(200.0, $summary['pending_amount']);
        $this->assertSame(400.0, $summary['outstanding_amount']);
        $this->assertSame(400.0, $summary['written_off_amount']);
        $this->assertSame(50.0, $summary['overpaid_amount']);

        $rows = $report->invoiceQuery(8, 2026)->orderBy('invoice_code')->get();
        $this->assertSame(['OVERPAID', 'REGULAR', 'WRITTEN-OFF'], $rows->pluck('invoice_code')->all());
        $this->assertSame(600.0, (float) $rows->firstWhere('invoice_code', 'REGULAR')->paid_amount);
        $this->assertSame(200.0, (float) $rows->firstWhere('invoice_code', 'REGULAR')->pending_amount);
    }

    public function test_admin_report_page_filters_period_and_rejects_unauthorized_or_invalid_requests(): void
    {
        [$contract, $room] = $this->context();
        $invoice = $this->invoice($contract, $room, 'REPORT-VISIBLE', '2026-08-05', 1000, Invoice::STATUS_PARTIAL);
        $this->payment($invoice, 400, '2026-08-10', Payment::STATUS_SUCCESS);
        $otherPeriod = $this->invoice($contract, $room, 'REPORT-HIDDEN', '2026-07-05', 500, Invoice::STATUS_UNPAID);
        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create(['name' => 'Admin báo cáo', 'email' => 'report-admin@example.test', 'role_id' => $adminRole->id, 'password' => 'password']);
        $client = $contract->tenant->user;

        $this->get(route('admin.reconciliation.index'))->assertRedirect('/login');
        $this->actingAs($client)->get(route('admin.reconciliation.index'))->assertForbidden();
        $response = $this->actingAs($admin)->get(route('admin.reconciliation.index', [
            'month' => 8,
            'year' => 2026,
        ]));

        $response->assertSuccessful()
            ->assertSee($invoice->invoice_code)
            ->assertDontSee($otherPeriod->invoice_code)
            ->assertViewHas('summary', fn (array $summary) => $summary['gross_billed'] === 1000.0
                && $summary['cohort_collected'] === 400.0
                && $summary['outstanding_amount'] === 600.0)
            ->assertViewHas('invoices', fn ($invoices) => $invoices->total() === 1);

        $this->get(route('admin.reconciliation.index', ['month' => 13, 'year' => 1999]))
            ->assertSessionHasErrors(['month', 'year']);
    }

    private function context(): array
    {
        $role = Role::create(['role_name' => 'User']);
        $user = User::create(['name' => 'Khách đối soát', 'email' => 'reconciliation@example.test', 'role_id' => $role->id, 'password' => 'password']);
        $tenant = Tenant::create(['user_id' => $user->id, 'full_name' => 'Khách đối soát', 'cccd' => '012345678901', 'phone' => '0900000000']);
        $room = Room::create(['room_code' => 'REC-01', 'floor' => 1, 'price' => 1000, 'area' => 20, 'status' => Room::STATUS_OCCUPIED]);
        $contract = Contract::query()->forceCreate(['contract_code' => 'REC-CONTRACT', 'room_id' => $room->id, 'tenant_id' => $tenant->id, 'monthly_rent' => 1000, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => Contract::STATUS_ACTIVE]);

        return [$contract, $room];
    }

    private function invoice(Contract $contract, Room $room, string $code, string $date, float $amount, string $status): Invoice
    {
        return Invoice::query()->forceCreate([
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'invoice_code' => $code,
            'invoice_type' => Invoice::TYPE_RENTAL,
            'revision' => Invoice::query()
                ->where('contract_id', $contract->id)
                ->where('invoice_type', Invoice::TYPE_RENTAL)
                ->where('month', (int) substr($date, 5, 2))
                ->where('year', (int) substr($date, 0, 4))
                ->count() + 1,
            'month' => (int) substr($date, 5, 2),
            'year' => (int) substr($date, 0, 4),
            'invoice_date' => $date,
            'due_date' => $date,
            'room_fee' => $amount,
            'electricity_fee' => 0,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => $amount,
            'status' => $status,
        ]);
    }

    private function payment(Invoice $invoice, float $amount, string $date, string $status): Payment
    {
        return Payment::query()->forceCreate([
            'invoice_id' => $invoice->id,
            'amount_paid' => $amount,
            'payment_date' => $date,
            'payment_method' => Payment::METHOD_CASH,
            'status' => $status,
        ]);
    }
}
