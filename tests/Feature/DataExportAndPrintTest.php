<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportAndPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_exports_and_admin_prints_require_an_active_admin(): void
    {
        [$client, , $contract, , $invoice] = $this->context('AUTH');
        $urls = [
            route('admin.rooms.export'), route('admin.rooms.export.download'),
            route('admin.tenants.export'), route('admin.tenants.export.download'),
            route('admin.invoices.export'), route('admin.invoices.export.download'),
            route('admin.invoices.payments.export'), route('admin.invoices.payments.export.download'),
            route('admin.contracts.print', $contract), route('admin.invoices.print', $invoice),
        ];

        foreach ($urls as $url) {
            $this->assertContains($this->get($url)->getStatusCode(), [302, 403]);
            $this->actingAs($client)->get($url)->assertForbidden();
        }
    }

    public function test_room_and_tenant_exports_apply_filters_and_keep_headers_when_empty(): void
    {
        $admin = $this->admin();
        [, $tenant, , $room] = $this->context('MATCH');
        $this->context('OTHER');

        $roomCsv = $this->csv($this->actingAs($admin)->get(route('admin.rooms.export.download', ['room_code' => $room->room_code])));
        $this->assertCount(2, $roomCsv);
        $this->assertSame($room->room_code, $roomCsv[1][0]);

        $tenantCsv = $this->csv($this->actingAs($admin)->get(route('admin.tenants.export.download')));
        $this->assertTrue(collect($tenantCsv)->contains(fn ($row) => ($row[0] ?? null) === $tenant->full_name));

        $empty = $this->csv($this->actingAs($admin)->get(route('admin.rooms.export.download', ['room_code' => 'NOT-FOUND'])));
        $this->assertCount(1, $empty);
    }

    public function test_invoice_export_filters_and_counts_only_successful_payments(): void
    {
        $admin = $this->admin();
        [, , , , $invoice] = $this->context('INV');
        $this->payment($invoice, 25000, Payment::STATUS_SUCCESS, 'SUCCESS-INV');
        $this->payment($invoice, 40000, Payment::STATUS_PENDING, 'PENDING-INV');

        $rows = $this->csv($this->actingAs($admin)->get(route('admin.invoices.export.download', [
            'month' => $invoice->month, 'year' => $invoice->year, 'status' => Invoice::STATUS_UNPAID, 'keyword' => 'INV',
        ])));

        $this->assertCount(2, $rows);
        $this->assertSame('25.000', $rows[1][5]);
        $this->assertSame('75.000', $rows[1][6]);
        $this->actingAs($admin)->get(route('admin.invoices.export.download', ['month' => 13, 'status' => 'invalid']))
            ->assertSessionHasErrors(['month', 'status']);
    }

    public function test_payment_export_filters_and_rejects_invalid_direct_requests(): void
    {
        $admin = $this->admin();
        [, , , , $invoice] = $this->context('PAY');
        $this->payment($invoice, 30000, Payment::STATUS_SUCCESS, 'BANK-MATCH', Payment::METHOD_BANK_TRANSFER);
        $this->payment($invoice, 10000, Payment::STATUS_FAILED, 'CASH-OTHER', Payment::METHOD_CASH);

        $rows = $this->csv($this->actingAs($admin)->get(route('admin.invoices.payments.export.download', [
            'status' => Payment::STATUS_SUCCESS, 'method' => Payment::METHOD_BANK_TRANSFER, 'keyword' => 'BANK-MATCH',
        ])));
        $this->assertCount(2, $rows);
        $this->assertSame('BANK-MATCH', $rows[1][0]);
        $this->actingAs($admin)->get(route('admin.invoices.payments.export.download', ['method' => 'card']))
            ->assertSessionHasErrors('method');
    }

    public function test_all_user_controlled_csv_cells_are_protected_from_formula_injection(): void
    {
        $admin = $this->admin();
        [, $tenant, , , $invoice] = $this->context('SAFE');
        $tenant->update(['full_name' => '=HYPERLINK("https://example.test")', 'address' => " \t+SUM(1,1)"]);
        $invoice->update(['invoice_code' => '@DANGEROUS']);
        $this->payment($invoice, 1000, Payment::STATUS_SUCCESS, '=PAYLOAD');

        $invoiceRows = $this->csv($this->actingAs($admin)->get(route('admin.invoices.export.download')));
        $paymentRows = $this->csv($this->actingAs($admin)->get(route('admin.invoices.payments.export.download')));
        $tenantRows = $this->csv($this->actingAs($admin)->get(route('admin.tenants.export.download')));

        $this->assertSame("'@DANGEROUS", $invoiceRows[1][0]);
        $this->assertSame("'=PAYLOAD", $paymentRows[1][0]);
        $this->assertStringStartsWith("'=HYPERLINK", $tenantRows[1][0]);
        $this->assertSame("' \t+SUM(1,1)", $tenantRows[1][4]);
        $this->assertSame('ordinary', Csv::safeCell('ordinary'));
    }

    public function test_admin_prints_contract_and_invoice_with_correct_successful_balance(): void
    {
        $admin = $this->admin();
        [, , $contract, , $invoice] = $this->context('PRINT');
        $this->payment($invoice, 30000, Payment::STATUS_SUCCESS, 'PRINT-OK');
        $this->payment($invoice, 50000, Payment::STATUS_PENDING, 'PRINT-PENDING');

        $this->actingAs($admin)->get(route('admin.contracts.print', $contract))
            ->assertSuccessful()->assertSee('Tenant PRINT')->assertSee('ROOM-PRINT');
        $this->actingAs($admin)->get(route('admin.invoices.print', $invoice))
            ->assertSuccessful()->assertSee($invoice->invoice_code)->assertSee('30.000')->assertSee('70.000');
        $this->actingAs($admin)->get('/admin/contracts/999999/print')->assertNotFound();
        $this->actingAs($admin)->get('/admin/invoices/999999/print')->assertNotFound();
    }

    public function test_client_can_print_only_an_owned_invoice(): void
    {
        [$owner, , , , $owned] = $this->context('OWNER');
        [, , , , $other] = $this->context('OTHEROWNER');

        $this->actingAs($owner)->get(route('client.invoices.print', $owned))
            ->assertSuccessful()->assertSee($owned->invoice_code);
        $this->actingAs($owner)->get(route('client.invoices.print', $other))->assertNotFound();
        $this->actingAs($owner)->get('/client/invoices/999999/print')->assertNotFound();
    }

    private function csv($response): array
    {
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['role_name' => 'Admin']);

        return User::create(['name' => 'Admin', 'email' => uniqid('admin').'@test.local', 'phone' => '090'.str_pad((string) User::count(), 7, '0', STR_PAD_LEFT), 'role_id' => $role->id, 'password' => 'Password@123', 'status' => User::STATUS_ACTIVE]);
    }

    private function context(string $suffix): array
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);
        $user = User::create(['name' => 'Client '.$suffix, 'email' => strtolower($suffix).'@export.test', 'phone' => '091'.str_pad((string) User::count(), 7, '0', STR_PAD_LEFT), 'role_id' => $role->id, 'password' => 'Password@123', 'status' => User::STATUS_ACTIVE]);
        $tenant = Tenant::create(['user_id' => $user->id, 'full_name' => 'Tenant '.$suffix, 'cccd' => 'CCCD-'.$suffix, 'phone' => $user->phone, 'email' => $user->email, 'address' => 'Address '.$suffix]);
        $room = Room::create(['room_code' => 'ROOM-'.$suffix, 'floor' => 1, 'price' => 3000000, 'area' => 25, 'status' => Room::STATUS_OCCUPIED]);
        $contract = Contract::create(['contract_code' => 'CONTRACT-'.$suffix, 'room_id' => $room->id, 'tenant_id' => $tenant->id, 'monthly_rent' => 3000000, 'deposit_amount' => 3000000, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => Contract::STATUS_ACTIVE]);
        $invoice = Invoice::create(['contract_id' => $contract->id, 'room_id' => $room->id, 'invoice_code' => 'INVOICE-'.$suffix, 'month' => 8, 'year' => 2026, 'invoice_date' => '2026-08-01', 'due_date' => '2026-08-10', 'room_fee' => 100000, 'total_amount' => 100000, 'status' => Invoice::STATUS_UNPAID]);

        return [$user, $tenant, $contract, $room, $invoice];
    }

    private function payment(Invoice $invoice, float $amount, string $status, string $code, string $method = Payment::METHOD_CASH): Payment
    {
        return Payment::create(['invoice_id' => $invoice->id, 'amount_paid' => $amount, 'payment_date' => '2026-08-05', 'payment_method' => $method, 'transaction_code' => $code, 'status' => $status]);
    }
}
