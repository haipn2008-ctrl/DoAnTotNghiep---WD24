<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Role;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.payment_webhook.secret' => 'webhook-test-secret']);
    }

    public function test_webhook_requires_the_configured_secret(): void
    {
        $this->postJson('/webhooks/payments', $this->payload('TX-AUTH', 'TT INV-202608-000001'))
            ->assertUnauthorized();

        $this->assertDatabaseCount('payment_webhook_events', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_webhook_matches_invoice_and_is_idempotent(): void
    {
        $invoice = $this->invoice('INV-202608-000001', 500000);
        $payload = $this->payload('BANK-TX-001', 'Thanh toan TT inv-202608-000001 phong tro', 500000);

        $this->withHeader('X-Webhook-Secret', 'webhook-test-secret')
            ->postJson('/webhooks/payments', $payload)
            ->assertOk()->assertJson(['success' => true, 'duplicate' => false, 'status' => 'matched']);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id, 'transaction_code' => 'BANK-TX-001',
            'amount_paid' => 500000, 'status' => Payment::STATUS_SUCCESS,
        ]);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);

        $this->withHeader('X-Webhook-Secret', 'webhook-test-secret')
            ->postJson('/webhooks/payments', $payload)
            ->assertOk()->assertJson(['duplicate' => true]);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_webhook_promotes_matching_pending_receipt_instead_of_creating_a_second_payment(): void
    {
        $invoice = $this->invoice('INV-202608-000002', 800000);
        $pending = Payment::create([
            'invoice_id' => $invoice->id, 'amount_paid' => 300000,
            'payment_date' => '2026-08-10', 'payment_method' => Payment::METHOD_QR,
            'status' => Payment::STATUS_PENDING,
        ]);

        $this->withHeader('X-Webhook-Secret', 'webhook-test-secret')->postJson(
            '/webhooks/payments',
            $this->payload('BANK-TX-002', 'TT INV-202608-000002', 300000)
        )->assertOk()->assertJson(['status' => 'matched']);

        $this->assertDatabaseCount('payments', 1);
        $pending = $pending->fresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $pending->status);
        $this->assertSame('BANK-TX-002', $pending->transaction_code);
        $this->assertSame(Invoice::STATUS_PARTIAL, $invoice->fresh()->status);
    }

    public function test_unmatched_transfer_is_kept_for_manual_reconciliation(): void
    {
        $this->withHeader('X-Webhook-Secret', 'webhook-test-secret')->postJson(
            '/webhooks/payments',
            $this->payload('BANK-TX-UNMATCHED', 'Chuyen tien phong tro', 100000)
        )->assertOk()->assertJson(['success' => true, 'status' => 'unmatched']);

        $this->assertDatabaseHas('payment_webhook_events', [
            'provider_transaction_id' => 'BANK-TX-UNMATCHED',
            'status' => PaymentWebhookEvent::STATUS_UNMATCHED,
        ]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_can_manually_reconcile_an_unmatched_webhook(): void
    {
        $invoice = $this->invoice('INV-202608-000003', 400000);
        $this->withHeader('X-Webhook-Secret', 'webhook-test-secret')->postJson(
            '/webhooks/payments',
            $this->payload('BANK-TX-MANUAL', 'Noi dung bi sai', 400000)
        )->assertOk();
        $event = PaymentWebhookEvent::firstOrFail();

        $this->post("/admin/payment-webhooks/{$event->id}/reconcile", [
            'invoice_code' => $invoice->invoice_code,
        ])->assertRedirect('/login');

        $adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Quản trị viên', 'email' => 'webhook-admin@example.test',
            'role_id' => $adminRole->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($admin)->post("/admin/payment-webhooks/{$event->id}/reconcile", [
            'invoice_code' => $invoice->invoice_code,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(PaymentWebhookEvent::STATUS_MATCHED, $event->fresh()->status);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertDatabaseHas('payments', ['transaction_code' => 'BANK-TX-MANUAL']);
    }

    public function test_stable_tenant_code_allocates_payment_to_oldest_open_invoices(): void
    {
        $first = $this->invoice('INV-202608-000010', 400000);
        $tenant = $first->contract->tenant;
        $second = Invoice::create([
            'contract_id' => $first->contract_id, 'room_id' => $first->room_id,
            'invoice_code' => 'INV-202609-000010', 'month' => 9, 'year' => 2026,
            'invoice_date' => '2026-09-05', 'due_date' => '2026-09-15',
            'room_fee' => 500000, 'total_amount' => 500000, 'status' => Invoice::STATUS_UNPAID,
        ]);

        $this->withHeader('X-Webhook-Secret', 'webhook-test-secret')->postJson(
            '/webhooks/payments',
            $this->payload('BANK-TENANT-CODE-001', 'THANH TOAN '.$tenant->payment_code, 600000)
        )->assertOk()->assertJson(['status' => 'matched']);

        $this->assertSame(Invoice::STATUS_PAID, $first->fresh()->status);
        $this->assertSame(Invoice::STATUS_PARTIAL, $second->fresh()->status);
        $this->assertSame(400000.0, (float) $first->paid_amount);
        $this->assertSame(200000.0, (float) $second->paid_amount);
        $this->assertDatabaseHas('payments', ['transaction_code' => 'BANK-TENANT-CODE-001-01']);
        $this->assertDatabaseHas('payments', ['transaction_code' => 'BANK-TENANT-CODE-001-02']);
    }

    private function payload(string $transactionId, string $content, int $amount = 100000): array
    {
        return [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'content' => $content,
            'transaction_date' => '2026-08-11 10:30:00',
        ];
    }

    private function invoice(string $code, int $amount): Invoice
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);
        $user = User::create([
            'name' => 'Khách webhook', 'email' => strtolower($code).'@example.test',
            'role_id' => $role->id, 'password' => 'password', 'status' => User::STATUS_ACTIVE,
        ]);
        $tenant = Tenant::create([
            'user_id' => $user->id, 'full_name' => 'Khách webhook '.$code,
            'cccd' => substr(str_pad((string) abs(crc32($code)), 12, '0'), 0, 12),
            'phone' => '09'.substr(str_pad((string) abs(crc32('phone-'.$code)), 8, '0'), 0, 8),
        ]);
        $room = Room::create([
            'room_code' => 'ROOM-'.substr($code, -6), 'floor' => 1, 'price' => 3000000,
            'area' => 20, 'status' => Room::STATUS_OCCUPIED,
        ]);
        $contract = Contract::create([
            'contract_code' => 'CONTRACT-'.substr($code, -6), 'room_id' => $room->id,
            'tenant_id' => $tenant->id, 'monthly_rent' => 3000000,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
            'status' => Contract::STATUS_ACTIVE,
        ]);

        return Invoice::create([
            'contract_id' => $contract->id, 'room_id' => $room->id, 'invoice_code' => $code,
            'month' => 8, 'year' => 2026, 'invoice_date' => '2026-08-05', 'due_date' => '2026-08-15',
            'room_fee' => $amount, 'total_amount' => $amount, 'status' => Invoice::STATUS_UNPAID,
        ]);
    }
}
