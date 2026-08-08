<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Room;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientInvoicePortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_client_only_sees_invoices_belonging_to_their_tenant_profile(): void
    {
        [$client, $ownContract, $ownRoom] = $this->createClientContext('A');
        [, $otherContract, $otherRoom] = $this->createClientContext('B');
        $ownInvoice = $this->createInvoice($ownContract, $ownRoom, 'INV-OWN');
        $this->createInvoice($otherContract, $otherRoom, 'INV-OTHER');

        $this->actingAs($client)
            ->get('/client/invoices')
            ->assertSuccessful()
            ->assertSee('INV-OWN')
            ->assertDontSee('INV-OTHER');

        $this->actingAs($client)
            ->get('/client/invoices/'.$ownInvoice->id)
            ->assertSuccessful()
            ->assertSee('Tiền điện')
            ->assertSee('20 kWh')
            ->assertDontSee('Chỉ số cũ')
            ->assertDontSee('Chỉ số mới');
    }

    public function test_client_cannot_open_or_print_another_tenants_invoice(): void
    {
        [$client] = $this->createClientContext('OWNER');
        [, $otherContract, $otherRoom] = $this->createClientContext('OTHER');
        $otherInvoice = $this->createInvoice($otherContract, $otherRoom, 'INV-PRIVATE');

        $this->actingAs($client)
            ->get('/client/invoices/'.$otherInvoice->id)
            ->assertNotFound();

        $this->actingAs($client)
            ->get('/client/invoices/'.$otherInvoice->id.'/print')
            ->assertNotFound();
    }

    public function test_client_payment_confirmation_stays_pending_until_admin_approves_it(): void
    {
        Storage::fake('public');
        [$client, $contract, $room] = $this->createClientContext('PAY');
        $invoice = $this->createInvoice($contract, $room, 'INV-PAY');

        $this->actingAs($client)
            ->post('/client/invoices/'.$invoice->id.'/payments', [
                'amount_paid' => 3070000,
                'payment_date' => '2026-07-09',
                'payment_method' => 'bank_transfer',
                'transaction_code' => 'FT-PAY-001',
                'proof_image' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect('/client/invoices/'.$invoice->id);

        $payment = Payment::firstOrFail();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame($client->id, $payment->submitted_by);
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->fresh()->status);
        Storage::disk('public')->assertExists($payment->proof_image);

        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin duyệt',
            'email' => 'payment-admin@example.com',
            'phone' => '0999999999',
            'role_id' => $adminRole->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->post('/admin/invoices/payments/'.$payment->id.'/approve')
            ->assertRedirect();

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->confirmed_by);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_client_cannot_submit_payment_for_another_tenants_invoice(): void
    {
        Storage::fake('public');
        [$client] = $this->createClientContext('PAYOWNER');
        [, $otherContract, $otherRoom] = $this->createClientContext('PAYOTHER');
        $otherInvoice = $this->createInvoice($otherContract, $otherRoom, 'INV-PAY-PRIVATE');

        $this->actingAs($client)
            ->post('/client/invoices/'.$otherInvoice->id.'/payments', [
                'amount_paid' => 100000,
                'payment_date' => '2026-07-09',
                'payment_method' => 'bank_transfer',
                'transaction_code' => 'INVALID',
                'proof_image' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_admin_can_reject_client_payment_without_reducing_invoice_balance(): void
    {
        [$client, $contract, $room] = $this->createClientContext('REJECTPAY');
        $invoice = $this->createInvoice($contract, $room, 'INV-REJECT');
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount_paid' => 500000,
            'payment_date' => '2026-07-09',
            'payment_method' => 'bank_transfer',
            'transaction_code' => 'REJECT-001',
            'status' => Payment::STATUS_PENDING,
            'submitted_by' => $client->id,
        ]);
        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin từ chối',
            'email' => 'reject-admin@example.com',
            'phone' => '0977777777',
            'role_id' => $adminRole->id,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->post('/admin/invoices/payments/'.$payment->id.'/reject', [
                'review_note' => 'Không tìm thấy giao dịch tương ứng.',
            ])
            ->assertRedirect();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->fresh()->status);
        $this->actingAs($client)
            ->get('/client/invoices/'.$invoice->id)
            ->assertSee('Không tìm thấy giao dịch tương ứng.');
    }

    public function test_client_only_sees_utility_usage_within_their_own_contract_periods(): void
    {
        [$client, $contract, $room] = $this->createClientContext('UTILITY');
        [, $otherContract, $otherRoom] = $this->createClientContext('UTILITYOTHER');

        $ownReading = UtilityReading::create([
            'room_id' => $room->id,
            'month' => 7,
            'year' => 2026,
            'record_date' => '2026-07-31',
            'electricity_old' => 100,
            'electricity_new' => 120,
            'water_old' => 40,
            'water_new' => 44,
            'status' => 'confirmed',
        ]);
        UtilityReading::create([
            'room_id' => $room->id,
            'month' => 12,
            'year' => 2025,
            'electricity_old' => 0,
            'electricity_new' => 888,
            'water_old' => 0,
            'water_new' => 888,
            'status' => 'confirmed',
        ]);
        UtilityReading::create([
            'room_id' => $otherRoom->id,
            'month' => 7,
            'year' => 2026,
            'electricity_old' => 0,
            'electricity_new' => 777,
            'water_old' => 0,
            'water_new' => 777,
            'status' => 'confirmed',
        ]);
        $invoice = $this->createInvoice($contract, $room, 'INV-UTILITY');
        $invoice->update(['utility_reading_id' => $ownReading->id]);

        $this->actingAs($client)
            ->get('/client/utilities')
            ->assertSuccessful()
            ->assertSeeText('20 kWh')
            ->assertSeeText('4 m³')
            ->assertSeeText('70.000đ')
            ->assertDontSeeText('777 kWh')
            ->assertDontSeeText('888 kWh')
            ->assertDontSee('Chỉ số cũ')
            ->assertDontSee('Chỉ số mới');
    }

    public function test_client_only_sees_their_own_room_and_contracts(): void
    {
        [$client, $ownContract, $ownRoom] = $this->createClientContext('PORTAL');
        [, $otherContract, $otherRoom] = $this->createClientContext('PORTALOTHER');

        $this->actingAs($client)
            ->get('/client/room')
            ->assertSuccessful()
            ->assertSee($ownRoom->room_code)
            ->assertDontSee($otherRoom->room_code);

        $this->actingAs($client)
            ->get('/client/contracts')
            ->assertSuccessful()
            ->assertSee($ownContract->contract_code)
            ->assertDontSee($otherContract->contract_code);

        $this->actingAs($client)
            ->get('/client/contracts/'.$otherContract->id)
            ->assertNotFound();
    }

    public function test_support_request_can_be_sent_reviewed_and_seen_only_by_its_owner(): void
    {
        Storage::fake('public');
        [$client, $contract] = $this->createClientContext('SUPPORT');
        [$otherClient] = $this->createClientContext('SUPPORTOTHER');

        $this->actingAs($client)
            ->post('/client/support', [
                'category' => 'utility',
                'subject' => 'Điện tăng bất thường',
                'description' => 'Nhờ ban quản lý kiểm tra lại mức sử dụng.',
                'attachment' => UploadedFile::fake()->image('meter.jpg'),
            ])
            ->assertRedirect();

        $supportRequest = SupportRequest::firstOrFail();
        $this->assertSame($client->id, $supportRequest->user_id);
        $this->assertSame($contract->id, $supportRequest->contract_id);
        Storage::disk('public')->assertExists($supportRequest->attachment);

        $this->actingAs($otherClient)
            ->get('/client/support')
            ->assertSuccessful()
            ->assertDontSee('Điện tăng bất thường');

        $adminRole = Role::create(['role_name' => 'Admin']);
        $admin = User::create([
            'name' => 'Admin hỗ trợ',
            'email' => 'support-admin@example.com',
            'phone' => '0988888888',
            'role_id' => $adminRole->id,
            'password' => 'password',
        ]);
        $this->actingAs($admin)
            ->get('/admin/support')
            ->assertSuccessful()
            ->assertSee('Điện tăng bất thường');

        $this->actingAs($admin)
            ->put('/admin/support/'.$supportRequest->id, [
                'status' => 'resolved',
                'admin_response' => 'Đã kiểm tra và xác nhận chỉ số đúng.',
            ])
            ->assertRedirect();

        $this->actingAs($client)
            ->get('/client/support')
            ->assertSuccessful()
            ->assertSee('Điện tăng bất thường')
            ->assertSee('Đã kiểm tra và xác nhận chỉ số đúng.');
    }

    public function test_client_can_update_contact_information_and_password(): void
    {
        [$client] = $this->createClientContext('ACCOUNT');

        $this->actingAs($client)
            ->put('/client/account', [
                'name' => 'Tên mới',
                'email' => 'new-account@example.com',
                'phone' => '0912345678',
            ])
            ->assertRedirect();

        $client->refresh();
        $this->assertSame('Tên mới', $client->name);
        $this->assertSame('new-account@example.com', $client->email);
        $this->assertSame('new-account@example.com', $client->tenant->email);
        $this->assertSame('0912345678', $client->tenant->phone);

        $this->actingAs($client)
            ->put('/client/account/password', [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password-123', $client->fresh()->password));
    }

    private function createClientContext(string $suffix): array
    {
        $role = Role::firstOrCreate(['role_name' => 'User']);
        $user = User::create([
            'name' => 'Khách '.$suffix,
            'email' => strtolower($suffix).'@client.test',
            'phone' => '090000'.str_pad((string) User::count(), 4, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'password' => 'password',
        ]);
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'full_name' => 'Khách thuê '.$suffix,
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'cccd' => 'CCCD-'.$suffix,
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => 'Hà Nội',
        ]);
        $room = Room::create([
            'room_code' => 'P-'.$suffix,
            'floor' => 1,
            'price' => 3000000,
            'area' => 25,
            'status' => 'occupied',
        ]);
        $contract = Contract::create([
            'contract_code' => 'HD-'.$suffix,
            'room_id' => $room->id,
            'tenant_id' => $tenant->id,
            'monthly_rent' => 3000000,
            'deposit_amount' => 3000000,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        return [$user, $contract, $room];
    }

    private function createInvoice(Contract $contract, Room $room, string $code): Invoice
    {
        $invoice = Invoice::create([
            'contract_id' => $contract->id,
            'room_id' => $room->id,
            'invoice_code' => $code,
            'month' => 7,
            'year' => 2026,
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-10',
            'room_fee' => 3000000,
            'electricity_fee' => 70000,
            'water_fee' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'total_amount' => 3070000,
            'status' => 'unpaid',
        ]);
        $invoice->details()->create([
            'type' => 'electricity',
            'name' => 'Tiền điện',
            'quantity' => 20,
            'unit' => 'kWh',
            'unit_price' => 3500,
            'amount' => 70000,
            'old_index' => 100,
            'new_index' => 120,
            'sort_order' => 1,
        ]);

        return $invoice;
    }
}
